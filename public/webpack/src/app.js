import 'bootstrap';
import 'bootstrap/dist/css/bootstrap.min.css';
import DrawerNavi from './js/DrawerNavi.js';
import SmoothScroll from './js/SmoothScroll.js';
import VerifySubmit from './js/VerifySubmit.js';
import './scss/style.scss';

window.addEventListener( 'load', () => {
	DrawerNavi();       // ナビゲーション
	SmoothScroll();     // スムーススクロール
	VerifySubmit( '#mailform' ); // フォームバリデーション
}, false );
