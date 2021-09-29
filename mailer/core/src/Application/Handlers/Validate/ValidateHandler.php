<?php
/**
 * Mailer | el.kulo v3.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Handlers\Validate;

use App\Application\Settings\SettingsInterface;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;
use Valitron\Validator;

class ValidateHandler
{

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
     * コンストラクタ
     *
     * @param SettingsInterface $settings
     * @return void
     */
    public function __construct(SettingsInterface $settings)
    {
        $this->server = $settings->get('config.server');
        $this->form = $settings->get('config.form');
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
        $this->checkNGWord();
    }

    /**
     * 必須項目チェック
     *
     * @return void
     */
    public function checkinRequired(): void
    {
        if (isset($this->form['REQUIRED_ATTRIBUTE'])) {
            $this->validate->rule(
                'required',
                $this->form['REQUIRED_ATTRIBUTE']
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
        if (isset($this->form['EMAIL_ATTRIBUTE'])) {
            Validator::addRule('EmailValidator', function ($field, $value) {
                return $this->isCheckMailFormat($value);
            });
            $this->validate->rule(
                'EmailValidator',
                $this->form['EMAIL_ATTRIBUTE']
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
        if (isset($this->form['MB_WORD'])) {
            Validator::addRule('MBValidator', function ($field, $value) {
                if (strlen($value) === mb_strlen($value, 'UTF-8')) {
                    return false;
                }
                return true;
            });
            $this->validate->rule(
                'MBValidator',
                $this->form['MB_WORD']
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
