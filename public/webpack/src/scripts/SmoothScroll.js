/*!
 * Mailer | el.kulo v3.3.1 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2022 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
const SmoothScroll = ( headHeight = 0 ) => {

	const run = () => {

		// スクロール処理を繰り返す間隔
		const interval = 10;

		// 近づく割合（数値が大きいほどゆっくり近く）
		const divisor = 8;

		// どこまで近づけば処理を終了するか(無限ループにならないように divisor から算出)
		const range = ( divisor / 2 ) + 1;
		const links = document.querySelectorAll( 'a[href*="#"]' );

		for ( let i = 0; i < links.length; i++ ) {
			links[i].addEventListener( 'click', e => {
				Scrolling( e );
			}, false );
		}

		const Scrolling = e => {

			// パスから#までの文字を切り抜いてリンク先の要素を取得
			const hrefAttr = e.currentTarget.getAttribute( 'href' );
			const hrefID = hrefAttr.slice( hrefAttr.lastIndexOf( '#' ) );
			const target = document.querySelector( hrefID );

			// ページ内にアンカー要素がなければ通常の遷移
			// または、URLが'#'の後に'/'がある場合は別ページとして判定
			if ( target && hrefAttr.lastIndexOf( '/' ) < hrefAttr.lastIndexOf( '#' ) ) {
				e.preventDefault();
			} else {
				return;
			}

			// 現在のスクロール値
			let nowY = window.pageYOffset;
			let toY;

			// ターゲットの座標取得
			// 現在のスクロール値 & ヘッダーの高さを踏まえた座標
			const targetRect = target.getBoundingClientRect();
			const targetY = targetRect.top + nowY - headHeight;

			// スクロール終了まで繰り返す処理
			( function Smooth() {
				toY = nowY + Math.round( ( targetY - nowY ) / divisor );
				window.scrollTo( 0, toY );
				nowY = toY;

				// 最下部にスクロールしても対象まで届かない場合は下限までスクロールして強制終了
				if ( document.body.clientHeight - window.innerHeight < toY ) {
					window.scrollTo( 0, document.body.clientHeight );
					return;
				}
				if ( toY >= targetY + range || toY <= targetY - range ) {

					//+-rangeの範囲内へ近くまで繰り返す
					window.setTimeout( Smooth, interval );
				} else {

					//+-range の範囲内にくれば正確な値へ移動して終了。
					window.scrollTo( 0, targetY );
				}
			}() );
		};
	};
	window.addEventListener( 'load', run, false );
};
export default SmoothScroll;
