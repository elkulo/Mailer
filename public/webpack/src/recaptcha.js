/*!
 * Mailer | el.kulo v3.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
const setReCaptcha = ( formID, siteKey, actionName = 'mailer' ) => {

	const formElement = document.querySelector( formID );

	if ( ! formElement || ! siteKey ) {
		return;
	}

	// reCAPTCHA用のDOM生成.
	const inputElement = {
		captchaResponse: document.createElement( 'input' ),
		captchaAction: document.createElement( 'input' ),
	};
	Object.keys( inputElement ).forEach( ( key ) => {
		if ( key === 'captchaResponse' ) {
			inputElement[key].setAttribute( 'name', '_recaptcha-response' );
			inputElement[key].setAttribute( 'value', '' );
		} else if ( key === 'captchaAction' ) {
			inputElement[key].setAttribute( 'name', '_recaptcha-action' );
			inputElement[key].setAttribute( 'value', actionName );
		}
		inputElement[key].setAttribute( 'type', 'hidden' );
		formElement.appendChild( inputElement[key]);
	});

	// トークンの更新.
	const changeToken = () => {
		const { grecaptcha } = window;
		if ( typeof grecaptcha !== 'object' ) {
			return;
		}
		grecaptcha.ready( () => {
			grecaptcha.execute( siteKey, { action: actionName })
				.then( ( token ) => {
					inputElement.captchaResponse.setAttribute( 'value', token );
				});
		});
	};

	// 更新トリガー.
	formElement.querySelectorAll( 'input, textarea' ).forEach( element => {
		element.addEventListener( 'blur', changeToken, false );
	});
	changeToken();
};

window.applyReCaptcha = ( formID, siteKey, actionName = 'mailer' ) => {
	document.addEventListener( 'DOMContentLoaded', () => {
		setReCaptcha( formID, siteKey, actionName );
	}, false );
};
