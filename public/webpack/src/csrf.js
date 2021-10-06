/**
 * Mailer | el.kulo v3.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
import axios from 'axios';

const setCSRF = ( formID, path = '' ) => {

	const formElement = document.querySelector( formID );

	const actionURL = formElement && formElement.getAttribute( 'action' );

	if ( ! actionURL ) {
		return;
	}

	// APIのURLを取得
	const api = path || actionURL.substr( 0, actionURL.indexOf( '/post' ) ) + '/api/v1/csrf';

	const inputElement = {
		csrfName: document.createElement( 'input' ),
		csrfValue: document.createElement( 'input' ),
	};

	// APIからCSRFを取得
	axios
		.get( api )
		.then( ({ data }) => {
			const { csrf } = data.data;
			Object.keys( inputElement ).forEach( ( key ) => {
				if ( key === 'csrfName' ) {
					inputElement[key].setAttribute( 'name', csrf.keys.name );
					inputElement[key].setAttribute( 'value', csrf.name );
				} else if ( key === 'csrfValue' ) {
					inputElement[key].setAttribute( 'name', csrf.keys.value );
					inputElement[key].setAttribute( 'value', csrf.value );
				}
				inputElement[key].setAttribute( 'type', 'hidden' );
				formElement.appendChild( inputElement[key]);
			});
		})
		.catch( ( error ) => {
			console.warn( error );
		});
};
window.applyCSRF = ( element, path = '' ) => {
	document.addEventListener( 'DOMContentLoaded', () => {
		setCSRF( element, path );
	}, false );
};
