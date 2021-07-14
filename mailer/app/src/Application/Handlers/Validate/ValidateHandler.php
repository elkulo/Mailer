<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Handlers\Validate;

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;
use Valitron;

class ValidateHandler implements ValidateHandlerInterface
{

    /**
     * 設定
     *
     * @var array
     */
    private array $setting = array();

    /**
     * バリデート
     *
     * @var object
     */
    private object $validate;

    /**
     * コンストラクタ
     *
     * @param  array $config
     * @return void
     */
    public function __construct(array $config)
    {
        $this->setting = $config;
    }

    /**
     * POSTデータをセット
     *
     * @param  array $post_data
     * @return void
     */
    public function set(array $post_data): void
    {
        Valitron\Validator::lang(VALIDATION_LANG);
        $this->validate = new Valitron\Validator($post_data);
        $this->validate->labels($this->setting['NAME_FOR_LABELS']);
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
        $this->checkNGWord();
    }

    /**
     * 必須項目チェック
     *
     * @return void
     */
    public function checkinRequired(): void
    {
        if (isset($this->setting['REQUIRED_ATTRIBUTE'])) {
            $this->validate->rule(
                'required',
                $this->setting['REQUIRED_ATTRIBUTE']
            );
        }
    }

    /**
     * メール形式チェック
     *
     * @return void
     */
    public function checkinEmail(): void
    {
        if (isset($this->setting['EMAIL_ATTRIBUTE'])) {
            Valitron\Validator::addRule('EmailValidator', function ($field, $value) {
                return $this->isCheckMailFormat($value);
            });
            $this->validate->rule(
                'EmailValidator',
                $this->setting['EMAIL_ATTRIBUTE']
            )->message('メールアドレスの形式が正しくありません。');
        }
    }

    /**
     * 日本語チェック
     *
     * @return void
     */
    public function checkinMBWord(): void
    {
        if (isset($this->setting['MB_WORD'])) {
            Valitron\Validator::addRule('MBValidator', function ($field, $value) {
                if (strlen($value) === mb_strlen($value, 'UTF-8')) {
                    return false;
                }
                return true;
            });
            $this->validate->rule(
                'MBValidator',
                $this->setting['MB_WORD']
            )->message('日本語を含まない文章は送信できません。');
        }
    }

    /**
     * 禁止ワード
     *
     * @return void
     */
    public function checkNGWord(): void
    {
        $ng_words = (array) explode(' ', $this->setting['NG_WORD']);
        if (isset($ng_words[0])) {
            Valitron\Validator::addRule('NGValidator', function ($field, $value) use ($ng_words) {
                foreach ($ng_words as $word) {
                    if (mb_strpos($value, $word, 0, 'UTF-8') !== false) {
                        return false;
                    }
                }
                return true;
            });
            $this->validate->rule(
                'NGValidator',
                '*'
            )->message('禁止ワードが含まれているため送信できません');
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
}
