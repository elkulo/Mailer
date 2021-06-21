/*!
* Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
* Copyright 2020-2021 A.Sudo
* Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
*/
import AppForm from './app-form/AppForm';
import './app-form/style.scss';
window.AppForm = ( element = '', config = {}) => new AppForm( element, config );
