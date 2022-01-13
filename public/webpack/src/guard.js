/*!
 * Mailer | el.kulo v3.3.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2022 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
import axios from 'axios';

const setGuard = ( formID, path = '' ) => {

	const formElement = document.querySelector( formID );

	const actionURL = formElement && formElement.getAttribute( 'action' );

	if ( ! actionURL ) {
		return;
	}

	// APIのURLを取得
	const api = path || actionURL.substr( 0, actionURL.indexOf( '/post' ) ) + '/api/v1/json';

	// Wrapper用のDOM生成.
	const wrapper = document.createElement( 'div' );
	wrapper.style.display = 'none';
	formElement.appendChild( wrapper );

	// CSRF用のDOM生成.
	const inputElement = {
		guardName: document.createElement( 'input' ),
		guardValue: document.createElement( 'input' ),
	};

	// APIからCSRFを取得
	axios
		.get( api )
		.then( ({ data }) => {
			const { guard } = data.data;
			Object.keys( inputElement ).forEach( ( key ) => {
				if ( key === 'guardName' ) {
					inputElement[key].setAttribute( 'name', guard.keys.name );
					inputElement[key].setAttribute( 'value', guard.name );
				} else if ( key === 'guardValue' ) {
					inputElement[key].setAttribute( 'name', guard.keys.value );
					inputElement[key].setAttribute( 'value', guard.value );
				}
				inputElement[key].setAttribute( 'type', 'hidden' );
				wrapper.appendChild( inputElement[key]);
			});
		})
		.catch( ( /* error */ ) => {
			/* console.warn( error ); */
		});
};
window.applyGuard = ( element, path = '' ) => {
	document.addEventListener( 'DOMContentLoaded', () => {
		setGuard( element, path );
	}, false );
};
