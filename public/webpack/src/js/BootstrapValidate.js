/*!
 * Mailer | el.kulo v3.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
const BootstrapValidate = ( element = '.needs-validation' ) => {

	// Fetch all the forms we want to apply custom Bootstrap validation styles to
	const forms = document.querySelectorAll( element );

	// Loop over them and prevent submission
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
};
export default BootstrapValidate;
