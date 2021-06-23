/*!
* Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
* Copyright 2020-2021 A.Sudo
* Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
*/
import 'bootstrap';
import 'bootstrap/dist/css/bootstrap.min.css';
import BootstrapVaridation from './js/BootstrapVaridation';
import DrawerNavi from './js/DrawerNavi';
import SmoothScroll from './js/SmoothScroll';
import './scss/global.scss';

// Bootstrap バリデーション.
window.addBootstrapVaridation = ( element ) => BootstrapVaridation( element );

// ナビゲーション.
DrawerNavi();

// スムーススクロール.
SmoothScroll();
