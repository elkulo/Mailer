<?php
/**
 * Mailer | el.kulo v3.3.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2022 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Handlers\Validate;

use App\Application\Settings\SettingsInterface;
use App\Application\Router\RouterInterface;
use Psr\Log\LoggerInterface;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;
use Valitron\Validator;
use ReCaptcha\ReCaptcha;

class ValidateHandler implements ValidateHandlerInterface
{

    /**
     * Google reCAPTCHAの閾値
     *
     * @var float
     */
    private $threshold = 0.5;

    /**
     * Google reCaptcha
     *
     * @var ReCaptcha
     */
    private $recaptcha;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * 設定情報
     *
     * @var SettingsInterface
     */
    private $settings;

    /**
     * バリデーション設定
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
     * ルーター
     *
     * @var RouterInterface
     */
    protected $router;

    /**
     * コンストラクタ
     *
     * @param  LoggerInterface $logger
     * @param  SettingsInterface $settings
     * @param  RouterInterface $router
     * @return void
     */
    public function __construct(LoggerInterface $logger, SettingsInterface $settings, RouterInterface $router)
    {
        $this->logger = $logger;
        $this->settings = $settings;
        $this->validateSettings = $settings->get('validate');
        $this->formSettings = $settings->get('form');
        $this->router = $router;

        // reCAPTCHAの閾値の変更.
        if ($this->validateSettings['RECAPTCHA_THRESHOLD']) {
            $this->threshold = $this->validateSettings['RECAPTCHA_THRESHOLD'];
        }

        // reCAPTCHAを初期化.
        if (!empty($this->validateSettings['RECAPTCHA_SECRETKEY'])) {
            $secretKey = $this->validateSettings['RECAPTCHA_SECRETKEY'];
            $this->recaptcha = new ReCaptcha($secretKey);
        }
    }

    /**
     * POSTデータをセット
     *
     * @param  array $posts
     * @return void
     */
    public function set(array $posts): void
    {
        Validator::lang($this->settings->get('siteLang'));
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
            $this->validate->rule(
                'required',
                $this->formSettings['REQUIRED_ATTRIBUTES']
            )->message($this->validateSettings['MESSAGE_REQUIRED_FIELD']);
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
            )->message($this->validateSettings['MESSAGE_EMAIL_FORMAT']);
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
            Validator::addRule('MultibyteValidator', function ($field, $value, $params, $fields) {
                try {
                    if (strlen($value) === mb_strlen($value, 'UTF-8')) {
                        throw new \Exception('Japanese was not included.');
                    }
                } catch (\Exception $e) {
                    if (! $this->validate->errors($field)) {
                        $this->logger->error($e->getMessage(), [
                            'email' => $this->getHiddenEmail($fields[$this->formSettings['EMAIL_ATTRIBUTE']]),
                            'subject' => $fields[$this->formSettings['SUBJECT_ATTRIBUTE']]
                        ]);
                    }
                    return false;
                }
                return true;
            });
            $this->validate->rule(
                'MultibyteValidator',
                $this->formSettings['MULTIBYTE_ATTRIBUTE']
            )->message($this->validateSettings['MESSAGE_MULTIBYTE_TEXT']);
        }
    }

    /**
     * 禁止ワード
     *
     * @return void
     */
    public function checkinBlockNGWord(): void
    {
        $blockWords = (array) explode(' ', $this->formSettings['BLOCK_NG_WORD']);
        if (isset($blockWords[0])) {
            Validator::addRule('BlockNGValidator', function ($field, $value, $params, $fields) use ($blockWords) {
                try {
                    foreach ($blockWords as $word) {
                        if (mb_strpos($value, $word, 0, 'UTF-8') !== false) {
                            throw new \Exception('Forbidden word was included.');
                        }
                    }
                } catch (\Exception $e) {
                    if (! $this->validate->errors($field)) {
                        $this->logger->error($e->getMessage(), [
                            'email' => $this->getHiddenEmail($fields[$this->formSettings['EMAIL_ATTRIBUTE']]),
                            'subject' => $fields[$this->formSettings['SUBJECT_ATTRIBUTE']]
                        ]);
                    }
                    return false;
                }
                return true;
            });
            $this->validate->rule(
                'BlockNGValidator',
                $this->formSettings['WORDFILTER_ATTRIBUTE']
            )->message($this->validateSettings['MESSAGE_FORBIDDEN_WORD']);
        }
    }

    /**
     * 禁止ドメイン
     *
     * @return void
     */
    public function checkinBlockDomain(): void
    {
        $blockDomains = $this->formSettings['BLOCK_DOMAINS'];
        if (isset($blockDomains[0])) {
            Validator::addRule('BlockDomainValidator', function ($field, $value, $params, $fields) use ($blockDomains) {
                try {
                    foreach ($blockDomains as $mail) {
                        if (strpos($value, $mail) !== false) {
                            throw new \Exception('Forbidden domain was included.');
                        }
                    }
                } catch (\Exception $e) {
                    if (! $this->validate->errors($field)) {
                        $this->logger->error($e->getMessage(), [
                            'email' => $this->getHiddenEmail($fields[$this->formSettings['EMAIL_ATTRIBUTE']]),
                            'subject' => $fields[$this->formSettings['SUBJECT_ATTRIBUTE']]
                        ]);
                    }
                    return false;
                }
                return true;
            });
            $this->validate->rule(
                'BlockDomainValidator',
                $this->formSettings['EMAIL_ATTRIBUTE']
            )->message($this->validateSettings['MESSAGE_FORBIDDEN_EMAIL']);
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
     * リファラチェック
     *
     * @return bool
     */
    public function isCheckReferer(): bool
    {
        try {
            $referer = isset($_SERVER['HTTP_REFERER'])?
                htmlspecialchars($_SERVER['HTTP_REFERER'], ENT_QUOTES, 'UTF-8'): '';
            if (strpos($referer, $this->settings->get('siteUrl')) === false) {
                throw new \Exception('Send from unknown referrer.');
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
        return true;
    }
    
    /**
     * BOT判定
     *
     * @return void
     */
    public function checkinHuman(): void
    {
        if (empty($this->validateSettings['RECAPTCHA_SECRETKEY'])) {
            return;
        }
        Validator::addRule('HumanValidator', function ($field, $value, $params, $fields) {
            try {
                if (isset($_SERVER['SERVER_NAME'], $_SERVER['REMOTE_ADDR'])) {
                    // 指定したアクション名を取得.
                    $action = isset($fields['_recaptcha-action'])? $fields['_recaptcha-action']: '';
                    if (! method_exists($this->recaptcha, 'setExpectedHostname')) {
                        throw new \Exception('reCAPTCHA configuration error.');
                    }
                    $response = $this->recaptcha->setExpectedHostname(filter_input(INPUT_SERVER, 'SERVER_NAME'))
                        ->setExpectedAction($action)
                        ->setScoreThreshold($this->threshold)
                        ->verify($value, filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP));
        
                    if (!$response->isSuccess()) {
                        throw new \Exception('reCAPTCHA score' . (string) $response->getScore());
                    }
                } else {
                    throw new \Exception('Unknown response.');
                }
            } catch (\Exception $e) {
                if (! $this->validate->errors($field)) {
                    $this->logger->error($e->getMessage(), [
                        'email' => $this->getHiddenEmail($fields[$this->formSettings['EMAIL_ATTRIBUTE']]),
                        'subject' => $fields[$this->formSettings['SUBJECT_ATTRIBUTE']],
                        'ip' => filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP) ?? ''
                    ]);
                }
                return false;
            }
            return true;
        });
        $this->validate->rule('HumanValidator', '_recaptcha-response')->message(
            $this->validateSettings['MESSAGE_UNKNOWN_ACCESS']
        );
        $this->validate->rule('required', ['_recaptcha-response', '_recaptcha-action'])->message('');
    }

    /**
     * Google reCAPTCHA
     *
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
                    $this->router->getUrl('recaptcha.min.js')
                ),
            ];
        } else {
            return [
                'key' => '',
                'script' => '',
            ];
        }
    }

    /**
     * 伏字のEmail
     *
     * @param  string $email
     * @return string
     */
    public function getHiddenEmail(string $email): string
    {
        return substr($email, 0, 1) . str_repeat('*', strlen(strstr($email, '@', true)) - 1) . strstr($email, '@');
    }
}
