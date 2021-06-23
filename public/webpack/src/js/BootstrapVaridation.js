/*!
 * Bootstrap v5.0.1 (https://getbootstrap.com/)
 * Copyright 2011-2021 The Bootstrap Authors (https://github.com/twbs/bootstrap/graphs/contributors)
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
 */

/*!
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */

const BootstrapVaridation = ( element = '.needs-validation' ) => {

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
export default BootstrapVaridation;
