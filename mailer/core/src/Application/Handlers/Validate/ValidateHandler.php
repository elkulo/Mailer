<?php
/**
 * Mailer | el.kulo v3.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Handlers\Validate;

use App\Application\Settings\SettingsInterface;
use App\Application\Router\RouterInterface;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;
use Valitron\Validator;
use ReCaptcha\ReCaptcha;

class ValidateHandler implements ValidateHandlerInterface
{

    /**
     * 設定情報
     *
     * @var array
     */
    private $validateSettings = [];

    /**
     * フォーム設定
     *
     * @var array
     */
    private $formSettings = [];

    /**
     * バリデート
     *
     * @var Validator
     */
    private $validate;

    /**
     * reCAPTCHAの閾値
     *
     * @var float
     */
    private $threshold = 0.5;

    /**
     * ルーター
     *
     * @var RouterInterface
     */
    protected $router;

    /**
     * コンストラクタ
     *
     * @param  SettingsInterface $settings
     * @param  RouterInterface $router
     * @return void
     */
    public function __construct(SettingsInterface $settings, RouterInterface $router)
    {
        $this->validateSettings = $settings->get('validate');
        $this->formSettings = $settings->get('form');
        $this->router = $router;
    }

    /**
     * POSTデータをセット
     *
     * @param  array $posts
     * @return void
     */
    public function set(array $posts): void
    {
        Validator::lang($this->validateSettings['VALIDATE_LANG']);
        $this->validate = new Validator($posts);
        $this->validate->labels($this->formSettings['NAME_FOR_LABELS']);
    }

    /**
     * バリデーションチェック
     *
     * @return bool
     */
    public function validate(): bool
    {
        return $this->validate->validate();
    }

    /**
     * バリデーションALLチェック
     *
     * @return bool
     */
    public function validateAll(): bool
    {
        $validateList = [
            fn() => $this->checkinRequired(),
            fn() => $this->checkinEmail(),
            fn() => $this->checkinMultibyteWord(),
            fn() => $this->checkinBlockNGWord(),
            fn() => $this->checkinBlockDomain(),
            fn() => $this->checkinHuman(),
        ];
        foreach ($validateList as $validateCheckFn) {
            if ($this->validate()) {
                $validateCheckFn();
            }
        }
        return $this->validate();
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
     * 必須項目チェック
     *
     * @return void
     */
    public function checkinRequired(): void
    {
        if (isset($this->formSettings['REQUIRED_ATTRIBUTES'])) {
            $this->validate->rule('required', $this->formSettings['REQUIRED_ATTRIBUTES']);
        }
    }

    /**
     * メール形式チェック
     *
     * @return void
     */
    public function checkinEmail(): void
    {
        if (isset($this->formSettings['EMAIL_ATTRIBUTE'])) {
            Validator::addRule('EmailValidator', function ($field, $value) {
                return $this->isCheckMailFormat($value);
            });
            $this->validate->rule(
                'EmailValidator',
                $this->formSettings['EMAIL_ATTRIBUTE']
            )->message('メールアドレスの形式が正しくありません。');
        }
    }

    /**
     * 日本語チェック
     *
     * @return void
     */
    public function checkinMultibyteWord(): void
    {
        if (isset($this->formSettings['MULTIBYTE_ATTRIBUTE'])) {
            Validator::addRule('MultibyteValidator', function ($field, $value) {
                if (strlen($value) === mb_strlen($value, 'UTF-8')) {
                    return false;
                }
                return true;
            });
            $this->validate->rule(
                'MultibyteValidator',
                $this->formSettings['MULTIBYTE_ATTRIBUTE']
            )->message('日本語を含まない文章は送信できません。');
        }
    }

    /**
     * 禁止ワード
     *
     * @return void
     */
    public function checkinBlockNGWord(): void
    {
        $ng_words = (array) explode(' ', $this->formSettings['BLOCK_NG_WORD']);
        if (isset($ng_words[0])) {
            Validator::addRule('BlockNGValidator', function ($field, $value) use ($ng_words) {
                foreach ($ng_words as $word) {
                    if (mb_strpos($value, $word, 0, 'UTF-8') !== false) {
                        return false;
                    }
                }
                return true;
            });
            $this->validate->rule('BlockNGValidator', '*')->message('禁止ワードが含まれているため送信できません。');
        }
    }

    /**
     * 禁止ドメイン
     *
     * @return void
     */
    public function checkinBlockDomain(): void
    {
        $block_domains = $this->formSettings['BLOCK_DOMAINS'];
        if (isset($block_domains[0])) {
            Validator::addRule('BlockDomainValidator', function ($field, $value) use ($block_domains) {
                foreach ($block_domains as $mail) {
                    if (strpos($value, $mail) !== false) {
                        return false;
                    }
                }
                return true;
            });
            $this->validate->rule(
                'BlockDomainValidator',
                $this->formSettings['EMAIL_ATTRIBUTE']
            )->message('指定のメールアドレスからの送信はお受けできません。');
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
        $secretKey = null;
        if (isset($this->validateSettings['RECAPTCHA_SECRETKEY'])) {
            $secretKey = $this->validateSettings['RECAPTCHA_SECRETKEY'];
        }

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
    public function getReCaptchaScript():array
    {
        // reCAPTCHA サイトキー
        $key = isset($this->validateSettings['RECAPTCHA_SITEKEY'])? $this->validateSettings['RECAPTCHA_SITEKEY']: '';
        if ($key) {
            return [
                'key' => trim(htmlspecialchars($key, ENT_QUOTES, 'UTF-8')),
                'script' => sprintf(
                    '<script src="https://www.google.com/recaptcha/api.js?render=%1$s"></script>
                     <script src="%2$s"></script>',
                    trim(htmlspecialchars($key, ENT_QUOTES, 'UTF-8')),
                    htmlspecialchars($this->router->getUrl('recaptcha.min.js'))
                ),
            ];
        } else {
            return [
                'key' => '',
                'script' => sprintf(
                    '<script src="%1$s"></script>',
                    htmlspecialchars($this->router->getUrl('recaptcha.min.js'))
                )
            ];
        }
    }
}
