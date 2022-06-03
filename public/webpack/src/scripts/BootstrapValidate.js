/*!
 * Mailer | el.kulo v3.3.2 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2022 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
const setValidate = ( element = '.needs-validation' ) => {

	// Fetch all the forms we want to apply custom Bootstrap validation styles to
	const forms = document.querySelectorAll( element );

	// Loop over them and prevent submission
	if ( forms[0]) {
		Array.from( forms ).forEach( ( form ) => {
			form.addEventListener(
				'submit',
				( event ) => {
					if ( ! form.checkValidity() ) {
						event.preventDefault();
						event.stopPropagation();
					}

					form.classList.add( 'was-validated' );
				},
				false
			);
		});
	}
};

export default () => {
	window.applyValidate = ( element = '.needs-validation' ) => {
		document.addEventListener( 'DOMContentLoaded', () => {
			setValidate( element );
		}, false );
	};
};
