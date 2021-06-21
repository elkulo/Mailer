import AppForm from './app-form/AppForm';
import './app-form/style.scss';
window.AppForm = ( element = '', config = {}) => new AppForm( element, config );
