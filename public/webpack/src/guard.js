/*!
 * Mailer | el.kulo v3.3.2 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2022 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
import axios from 'axios';

const setGuard = ( formID, path = '' ) => {

	const formElement = document.querySelector( formID );

	const actionURL = formElement?.getAttribute( 'action' );

	// CSRF用のDOM生成.
	const virtualDOM = {
		wrapper: document.createElement( 'div' ),
		input: {
			guardName: document.createElement( 'input' ),
			guardValue: document.createElement( 'input' ),
		},
	};

	/**
	 * 初期化
	 *
	 * @return {void}
	 */
	const init = () => {

		// APIのURLを取得
		const api = path || actionURL.substr( 0, actionURL.indexOf( '/post' ) ) + '/api/v1/json';

		// Wrapperの配置.
		virtualDOM.wrapper.style.display = 'none';
		formElement.appendChild( virtualDOM.wrapper );

		// APIからCSRFを取得
		axios
			.get( api )
			.then( ({ data }) => {
				const { guard } = data.data;
				Object.keys( virtualDOM.input ).forEach( ( key ) => {
					if ( key === 'guardName' ) {
						virtualDOM.input[key].setAttribute( 'name', guard.keys.name );
						virtualDOM.input[key].setAttribute( 'value', guard.name );
					} else if ( key === 'guardValue' ) {
						virtualDOM.input[key].setAttribute( 'name', guard.keys.value );
						virtualDOM.input[key].setAttribute( 'value', guard.value );
					}
					virtualDOM.input[key].setAttribute( 'type', 'hidden' );
					virtualDOM.wrapper.appendChild( virtualDOM.input[key]);
				});
			})
			.catch( ( /* error */ ) => {
			/* console.warn( error ); */
			});
	};

	if ( actionURL ) {
		init();
	}
};

window.applyGuard = ( element, path = '' ) => {
	document.addEventListener( 'DOMContentLoaded', () => {
		setGuard( element, path );
	}, false );
};
