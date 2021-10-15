/*!
 * Mailer | el.kulo v3.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
const setReCaptcha = ( formID, siteKey, actionName = 'mailer' ) => {

	// リロードタイマー.
	const reloadState = {
		seconds: 2,
		timer: null
	};

	const formElement = document.querySelector( formID );

	if ( ! formElement || ! siteKey ) {
		return;
	}

	// Wrapper用のDOM生成.
	const wrapper = document.createElement( 'div' );
	wrapper.style.display = 'none';
	formElement.appendChild( wrapper );

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
		wrapper.appendChild( inputElement[key]);
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
		if ( reloadState.timer ) {
			clearTimeout( reloadState.timer );
		}
		reloadState.timer = setTimeout( () => changeToken(), 60 * 1000 * reloadState.seconds );
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
