/*!
 * Mailer | el.kulo v3.4.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2023 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
const reCAPTCHA = {
	data: {
		config: {
			formIDs: [],
			siteKey: '',
			actionName: ''
		},
		grecaptcha: window?.grecaptcha,
	},

	/**
	 * Setup
	 *
	 * @param {Object} props
	 * @return {void}
	 */
	setup( props ) {
		const { formIDs, siteKey, actionName } = props;
		const formDOM = document.querySelectorAll( formIDs )?.[0];

		// 初期値.
		const state = {
			minute: 1, // 毎分更新(キーの有効期限2分の内1分有余).
			timer: 0,
			isPeriod: true,
			hasToken: true,
		};

		// 仮想DOMを生成.
		const virtualDOM = {
			wrapper: document.createElement( 'div' ),
			input: {
				captchaResponse: document.createElement( 'input' ),
				captchaAction: document.createElement( 'input' ),
			},
		};

		/**
		 * 初期化
		 *
		 * @return {void}
		 */
		const init = () => {

			// 非表示で囲う要素.
			virtualDOM.wrapper.style.display = 'none';
			formDOM.appendChild( virtualDOM.wrapper );

			// reCAPTCHA用の要素.
			Object.keys( virtualDOM.input ).forEach( ( key ) => {
				if ( key === 'captchaResponse' ) {
					virtualDOM.input[key].setAttribute(
						'name',
						'_recaptcha-response'
					);
					virtualDOM.input[key].setAttribute( 'value', '' );
				} else if ( key === 'captchaAction' ) {
					virtualDOM.input[key].setAttribute(
						'name',
						'_recaptcha-action'
					);
					virtualDOM.input[key].setAttribute( 'value', actionName );
				}
				virtualDOM.input[key].setAttribute( 'type', 'hidden' );
				virtualDOM.wrapper.appendChild( virtualDOM.input[key]);
			});
		};

		/**
		 * トークンの追加
		 *
		 * @return {Promise<void>}
		 */
		const addToken = async() => {
			const { grecaptcha } = this.data;

			/**
			 * 作成
			 *
			 * @return {Promise<void>}
			 */
			const create = () => {

				// 送信後は無効.
				if ( formDOM.dataset.status === 'sent' ) {
					virtualDOM.input.captchaResponse.setAttribute( 'value', '' );
					state.hasToken = false;
					return Promise.reject();
				}

				// 新しいトークンを取得.
				return new Promise( ( resolve, reject ) => {

					// トークンの更新受付を停止.
					state.isPeriod = false;

					// Google reCAPTCHAから取得.
					grecaptcha.ready( () => {
						try {
							grecaptcha
								.execute( siteKey, { action: actionName })
								.then( ( token ) => resolve( token ) );
						} catch ( error ) {
							reject( error );
						}
					});
				})
					.then( ( token ) => {
						virtualDOM.input.captchaResponse.setAttribute(
							'value',
							token
						);
					})
					.catch( ( error ) => {
						if ( error ) {
							console.error( '無効なreCAPTHCAのサイトキー' ); // eslint-disable-line no-console
						}
						state.hasToken = false;
					})
					.finally( () => {
						state.timer = setTimeout( () => {

							// タイマーを停止.
							clearTimeout( state.timer );

							// トークンの更新受付を開始.
							state.isPeriod = true;
						}, Math.floor( 1000 * 60 * state.minute ) );
					});
			};

			/**
			 * アップデート
			 *
			 * @return {Promise<void>}
			 */
			const update = () => {

				// 初回でreCAPTCHAが確認できなければ更新は無効.
				if ( ! state.hasToken ) {
					return Promise.reject();
				}

				// 期限の場合に再取得.
				const textareaEvent = () => state.isPeriod && create();

				// textareaからフォーカスが外れた時が更新のトリガー.
				const textareaDOMs = formDOM.querySelectorAll( 'textarea' );
				textareaDOMs.forEach( ( element ) => {
					element.addEventListener( 'input', textareaEvent, false );
				});
				return Promise.resolve();
			};

			// 順番に起動.
			await create();
			await update();
		};

		/**
		 * セットアップ完了
		 *
		 * @return {void}
		 */
		const ready = () => {
			if ( formDOM && siteKey && actionName ) {
				init();
				addToken();
			}
		};

		// チェーンメソッドで返却.
		return {
			ready,
		};
	},

	/**
	 * マウント.
	 *
	 * @return {void}
	 */
	mount() {
		const { config, grecaptcha } = this.data;
		if ( typeof grecaptcha === 'object' ) {
			this.setup({
				formIDs: config?.formIDs,
				siteKey: config?.siteKey,
				actionName: config?.actionName,
			}).ready();
		}
	},
};

window.applyReCaptcha = ( formID, siteKey, actionName = 'mailer' ) => {
	reCAPTCHA.data.config = { formIDs: [formID], siteKey: siteKey, actionName: actionName };
	document.addEventListener( 'DOMContentLoaded', () => reCAPTCHA.mount(), false );
};
