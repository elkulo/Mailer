import 'bootstrap';
import 'bootstrap/dist/css/bootstrap.min.css';
import DrawerNavi from './js/DrawerNavi';
import SmoothScroll from './js/SmoothScroll';
import VerifySubmit from './js/VerifySubmit';
import './scss/style.scss';

// ナビゲーション.
DrawerNavi();

// スムーススクロール.
SmoothScroll();

// フォームバリデーション.
VerifySubmit( '#mailform' );
