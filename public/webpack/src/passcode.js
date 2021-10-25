/*!
 * Mailer | el.kulo v3.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
const setPasscode = () => {

	// Emailの入力.
	document.querySelector( '#admin-email' ) && document.querySelector( '#admin-email' ).focus();

	// パスコードの入力
	const passcodes = document.querySelectorAll( '.passcode' );
	if ( passcodes[0]) {
		passcodes[0].focus();
		passcodes.forEach( ( passcodeElement, idx ) => {

			// キー入力でinput要素のフォーカス調整.
			passcodeElement.addEventListener( 'keydown', ( e ) => {
				if ( e.key && 0 <= e.key && e.key <= 9 ) {
					if ( idx < passcodes.length - 1 ) {
						setTimeout( () => passcodes[idx + 1].focus(), 10 );
					} else {
						setTimeout( () => document.querySelector( '#inputPasscodeSubmit' ).focus(), 10 );
					}
				} else if ( idx && e.key === 'Backspace' ) {
					setTimeout( () => passcodes[idx - 1].focus(), 10 );
				}
			});

			// フォーカスアウト時に入力文字を検査.
			passcodeElement.addEventListener( 'blur', () => {
				let inputValue = passcodes[idx].value || '';
				inputValue = inputValue.replace( /[Ａ-Ｚａ-ｚ０-９]/g, ( s ) => String.fromCharCode( s.charCodeAt( 0 ) - 0xFEE0 ) );
				if ( ! isNaN( inputValue ) && 0 <= inputValue && inputValue <= 9 ) {
					passcodes[idx].value = inputValue.trim();
				} else {
					passcodes[idx].value = '';
				}
			});

			// ペースト時にパスコードを配分.
			passcodeElement.addEventListener( 'paste', ( event ) => {
				event.preventDefault();
				const words = ( event.clipboardData || window.clipboardData ).getData( 'text' ).split( '' );
				passcodes.forEach( ( input, idx ) => {
					const inputValue = words[idx] || '';
					if ( ! isNaN( inputValue ) && 0 <= inputValue && inputValue <= 9 ) {
						input.value = inputValue.trim();
					} else {
						input.value = '';
					}
				});
			});
		});
	}
};
document.addEventListener( 'DOMContentLoaded', setPasscode, false );
