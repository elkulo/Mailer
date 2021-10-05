<?php
/**
 * Mailer | el.kulo v3.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Handlers\Validate;

use App\Application\Settings\SettingsInterface;
use Psr\Log\LoggerInterface;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;
use Valitron\Validator;
use ReCaptcha\ReCaptcha;

class ValidateHandler
{

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * サーバー設定
     *
     * @var array
     */
    private array $server = array();

    /**
     * フォーム設定
     *
     * @var array
     */
    private array $form = array();

    /**
     * バリデート
     *
     * @var Validator
     */
    private Validator $validate;

    /**
     * しきい値
     *
     * @var float
     */
    private float $threshold = 0.5;

    /**
     * コンストラクタ
     *
     * @param  SettingsInterface $settings
     * @param  LoggerInterface $logger
     * @return void
     */
    public function __construct(SettingsInterface $settings, LoggerInterface $logger)
    {
        $this->server = $settings->get('config.server');
        $this->form = $settings->get('config.form');
        $this->logger = $logger;
    }

    /**
     * POSTデータをセット
     *
     * @param  array $post_data
     * @return void
     */
    public function set(array $post_data): void
    {
        Validator::lang($this->server['VALIDATION_LANG']);
        $this->validate = new Validator($post_data);
        $this->validate->labels($this->form['NAME_FOR_LABELS']);
    }

    /**
     * バリデーションBoolType
     *
     * @return bool
     */
    public function validate(): bool
    {
        return $this->validate->validate();
    }

    /**
     * エラー内容
     *
     * @return array
     */
    public function errors(): array
    {
        return $this->validate->errors();
    }

    /**
     * バリデーションチェック
     *
     * @return void
     */
    public function checkinValidateAll(): void
    {
        $this->checkinRequired();
        $this->checkinEmail();
        $this->checkinMBWord();
        $this->checkinNGWord();
        $this->checkinHuman();
    }

    /**
     * 必須項目チェック
     *
     * @return void
     */
    public function checkinRequired(): void
    {
        if (isset($this->form['REQUIRED_ATTRIBUTE'])) {
            $this->validate->rule('required', $this->form['REQUIRED_ATTRIBUTE']);
        }
    }

    /**
     * メール形式チェック
     *
     * @return void
     */
    public function checkinEmail(): void
    {
        if (isset($this->form['EMAIL_ATTRIBUTE'])) {
            Validator::addRule('EmailValidator', function ($field, $value) {
                return $this->isCheckMailFormat($value);
            });
            $this->validate->rule('EmailValidator', $this->form['EMAIL_ATTRIBUTE'])->message('メールアドレスの形式が正しくありません。');
        }
    }

    /**
     * 日本語チェック
     *
     * @return void
     */
    public function checkinMBWord(): void
    {
        if (isset($this->form['MB_WORD'])) {
            Validator::addRule('MBValidator', function ($field, $value) {
                if (strlen($value) === mb_strlen($value, 'UTF-8')) {
                    return false;
                }
                return true;
            });
            $this->validate->rule('MBValidator', $this->form['MB_WORD'])->message('日本語を含まない文章は送信できません。');
        }
    }

    /**
     * 禁止ワード
     *
     * @return void
     */
    public function checkinNGWord(): void
    {
        $ng_words = (array) explode(' ', $this->form['NG_WORD']);
        if (isset($ng_words[0])) {
            Validator::addRule('NGValidator', function ($field, $value) use ($ng_words) {
                foreach ($ng_words as $word) {
                    if (mb_strpos($value, $word, 0, 'UTF-8') !== false) {
                        return false;
                    }
                }
                return true;
            });
            $this->validate->rule('NGValidator', '*')->message('禁止ワードが含まれているため送信できません。');
        }
    }

    /**
     * メール文字判定
     *
     * @param  string $value
     * @return bool
     */
    public function isCheckMailFormat(string $value): bool
    {
        $validator = new EmailValidator();
        $multipleValidations = new MultipleValidationWithAnd([
            new RFCValidation(),
            new DNSCheckValidation()
        ]);
        //ietf.org has MX records signaling a server with email capabilites
        return $validator->isValid(trim($value), $multipleValidations); //true
    }

    /**
     * Google reCAPTCHA
     *
     * @param  string $token
     * @param  string $action
     * @return void
     */
    public function checkinHuman(): void
    {
        // reCAPTCHA シークレットキー
        $secretKey = isset($this->server['CAPTCHA']['SECRETKEY'])? $this->server['CAPTCHA']['SECRETKEY']: null;

        if ($secretKey) {
            Validator::addRule('HumanValidator', function ($field, $value, $params, $fields) use ($secretKey) {
                try {
                    if (isset($_SERVER['SERVER_NAME'], $_SERVER['REMOTE_ADDR'])) {
                        // 指定したアクション名を取得.
                        $action = isset($fields['_recaptcha-action'])? $fields['_recaptcha-action']: '';

                        $recaptcha = new ReCaptcha($secretKey);
                        $response = $recaptcha->setExpectedHostname($_SERVER['SERVER_NAME'])
                            ->setExpectedAction($action)
                            ->setScoreThreshold($this->threshold)
                            ->verify($value, $_SERVER['REMOTE_ADDR']);
        
                        if (!$response->isSuccess()) {
                            throw new \Exception($response->getErrorCodes()[0]);
                        }
                    }
                } catch (\Exception $e) {
                    return false;
                }
                return true;
            });
            $this->validate->rule('HumanValidator', '_recaptcha-response')->message('ロボットによる投稿は受け付けていません。');
            $this->validate->rule('required', ['_recaptcha-response', '_recaptcha-action'])->message('');
        }
    }

    /**
     * Google reCAPTCHA
     *
     * @param  string $token
     * @param  string $action
     * @return array
     */
    public function getCaptchaScript():array
    {
        return [
            'key' => $this->server['CAPTCHA']['SITEKEY'], // reCAPTCHA サイトキー
            'script' => sprintf(
                '<script src="https://www.google.com/recaptcha/api.js?render=%1$s"></script>',
                trim(htmlspecialchars($this->server['CAPTCHA']['SITEKEY'], ENT_QUOTES, 'UTF-8'))
            ),
        ];
    }
}
