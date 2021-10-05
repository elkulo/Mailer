/**
 * Mailer | el.kulo v3.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */

const setCaptcha = ( formID, siteKey, actionName ) => {
	const formElement = document.querySelector( formID );
	if ( ! formElement || ! siteKey ) {
		return;
	}

	// reCAPTCHA用のDOM生成.
	const inputElement = {
		captchaResponse: document.createElement( 'input' ),
		captchaAction: document.createElement( 'input' ),
	};

	// reCAPTCHAの取得.
	if ( typeof window.grecaptcha === 'object' ) {
		const { grecaptcha } = window;
		grecaptcha.ready( () => {
			grecaptcha.execute( siteKey, { action: actionName })
				.then( ( token ) => {
					Object.keys( inputElement ).forEach( ( key ) => {
						if ( key === 'captchaResponse' ) {
							inputElement[key].setAttribute( 'name', '_recaptcha-response' );
							inputElement[key].setAttribute( 'value', token );
						} else if ( key === 'captchaAction' ) {
							inputElement[key].setAttribute( 'name', '_recaptcha-action' );
							inputElement[key].setAttribute( 'value', actionName );
						}
						inputElement[key].setAttribute( 'type', 'hidden' );
						formElement.appendChild( inputElement[key]);
					});
				})
				.catch( ( error ) => {
					console.warn( error );
				});
		});
	}
};

window.applyCaptcha = ( formID, siteKey, actionName = 'mailer' ) => {
	document.addEventListener( 'DOMContentLoaded', () => {
		setCaptcha( formID, siteKey, actionName );
	}, false );
};
