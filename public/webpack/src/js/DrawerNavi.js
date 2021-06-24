/*!
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
const DrawerNavi = () => {
	const run = () => {
		const $body = document.body;
		const $drawerOpenButton = document.querySelector( '.menu-trigger' );
		const $mainNavi = document.querySelector( '.main-navigation' );
		const $mainNaviList = $mainNavi.querySelectorAll( 'a' );
		let timer = 0;

		// メニュートグルボタン
		const addDrawerToggle = ( e ) => {
			$body.classList.toggle( 'drawer-on' );
			$mainNavi.classList.toggle( 'toggle-on' );

			if ( e.type === 'resize' ) {
				$body.classList.remove( 'drawer-on' );
				$mainNavi.classList.remove( 'toggle-on' );
			}
		};

		$drawerOpenButton.addEventListener(
			'click',
			( e ) => {
				e.preventDefault();
				addDrawerToggle( e );
			},
			false
		);

		for ( let i = 0; i < $mainNaviList.length; i++ ) {
			$mainNaviList[i].addEventListener(
				'click',
				( e ) => {
					if ( $body.classList.contains( 'drawer-on' ) ) {
						addDrawerToggle( e );
					}
				},
				false
			);
		}

		window.addEventListener(
			'resize',
			( e ) => {
				if ( 0 < timer ) {
					clearTimeout( timer );
				}
				timer = setTimeout( () => {
					addDrawerToggle( e );
				}, 100 );
			},
			false
		);

		// サブメニューのボタン
		let $button = document.createElement( 'button' );
		$button.classList.add( 'dropdown-toggle' );
		$button.setAttribute( 'aria-expanded', 'true' );
		$button.innerHTML =
      '<svg class="icon icon-angle-down" aria-hidden="true" role="img"><use href="#icon-angle-down" xlink:href="#icon-angle-down"></use></svg>';

		let $subMenu = document.querySelectorAll( '.sub-menu' );

		// ボタンノードの複製の器
		let $buttonClone;

		// ボタンノードの複製を追加
		for ( let i = 0; i < $subMenu.length; i++ ) {
			$buttonClone = $button.cloneNode( true );
			$subMenu[i].parentNode.insertBefore( $buttonClone, $subMenu[i]);

			$buttonClone.addEventListener(
				'click',
				( e ) => {
					e.preventDefault();
					e.target.parentNode.parentNode
						.querySelector( '.current-menu-ancestor > .sub-menu' )
						.classList.toggle( 'toggled-on' );
				},
				false
			);
		}
	};
	window.addEventListener( 'load', run, false );
};
export default DrawerNavi;
