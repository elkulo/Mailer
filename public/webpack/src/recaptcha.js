/*!
 * Mailer | el.kulo v3.3.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2022 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
const setReCaptcha = ( formID, siteKey, actionName = 'mailer' ) => {
	const formElement = document.querySelector( formID );
	const { grecaptcha } = window;

	if ( ! formElement || ! siteKey || typeof grecaptcha !== 'object' ) {
		return;
	}

	// リロードタイマー.
	const reloadState = {
		minute: 2,
		timer: null,
		limit: 5,
		count: 0,
	};
	let hasToken = true;

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

	// トークンの自動更新.
	const autoChangeToken = ( eventType = null ) => {
		if ( reloadState.timer ) {
			clearTimeout( reloadState.timer );
		}
		if ( reloadState.count < reloadState.limit ) {
			reloadState.timer = setTimeout(
				() => changeToken(),
				60 * 1000 * reloadState.minute
			);
		}
		if ( eventType === 'focus' ) {
			reloadState.count = 0;
		} else {
			reloadState.count++;
		}
	};

	// トークンの更新.
	const changeToken = ( e = null ) => {
		if ( ! hasToken ) {
			return; // 初回でreCAPTCHAが確認できなければ二回目以降は無効.
		}
		new Promise( ( resolve, reject ) => {
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
				inputElement.captchaResponse.setAttribute(
					'value',
					token
				);
				autoChangeToken( e && e.type === 'focus' );
			})
			.catch( ( error ) => {
				if ( error ) {
					console.error( '無効なreCAPTHCAのサイトキー' ); // eslint-disable-line no-console
				}
				hasToken = false;
			});
	};

	// 更新トリガー.
	formElement.querySelectorAll( 'input, textarea' ).forEach( element => {
		element.addEventListener( 'focus', ( e ) => changeToken( e ), false );
	});
	changeToken();
};

window.applyReCaptcha = ( formID, siteKey, actionName = 'mailer' ) => {
	document.addEventListener( 'DOMContentLoaded', () => {
		setReCaptcha( formID, siteKey, actionName );
	}, false );
};
