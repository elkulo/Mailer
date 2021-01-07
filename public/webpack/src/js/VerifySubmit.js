/*
 * VerifySubmit@1.3.0
 */
const VerifySubmit = ( $formElement = '#mailform' ) => {

	const $mailform = document.querySelector( $formElement + ':not([novalidate])' );

	// form要素がなければ終了
	if ( ! $mailform ) {
		return;
	}

	// 送信ボタンの取得
	let $submit = $mailform.querySelector( '[type="submit"]' );

	// 初期値で送信イベントを無効
	let preventEvent = true;

	// エラーメッセージボックス生成
	let $messageError = document.createElement( 'div' );
	$messageError.classList.add( 'js__error--message' );
	$messageError.style.opacity = 0;
	$mailform.insertBefore( $messageError, $mailform.firstElementChild );

	// 必須チェック
	const ConfirmRequired = () => {

		// エラーの初期化
		let errorItem = [];
		$messageError.style.opacity = 0;

		let $errorElements = $mailform.querySelectorAll( '.js__error--field' );
		if ( $errorElements.length ) {
			for ( let i = 0; i < $errorElements.length; i++ ) {
				$errorElements[i].classList.remove( 'js__error--field' );
			}
		}

		// 項目チェック
		// required属性のあるフォームを取得
		const $inputs = $mailform.querySelectorAll( '[name]' );
		const $validItems = [].slice.call( $inputs ).filter( ( item ) => {
			return item.required;
		});

		if ( $validItems.length ) {
			$validItems.forEach( ( item ) => {

				// 必須項目のチェック
				errorItem = applyFilterRequired( item, errorItem );

				// メールアドレスのチェック
				errorItem = applyFilterMailAddress( item, errorItem );

				// 電話番号のチェック
				errorItem = applyFilterPhoneNumber( item, errorItem );

				// 数値のチェック
				errorItem = applyFilterInteger( item, errorItem );

			});
		}

		// エラー判定
		if ( errorItem.length ) {

			// エラーの結合
			const result = Object.keys( errorItem ).map( ( item ) => {
				return '<li>' + errorItem[item] + '</li>';
			}).join( '' );

			// エラーメッセージをセット
			$messageError.innerHTML = ( '<ul>' + result + '</ul>' );
			$messageError.style.opacity = 1;
			removeError();
		} else {

			// エラーなしの場合、送信処理を有効にして送信イベントを再開
			preventEvent = false;
			$submit.click();
			return;
		}
	};

	// 送信イベント
	$submit.addEventListener( 'click', e => {
		if ( preventEvent ) {

			// 送信を一度無効にして必須チェック
			e.preventDefault();
			ConfirmRequired();
		} else {

			// 送信処理後に再び無効にする
			preventEvent = true;
		}
	}, false );

	// 必須項目の記入確認
	const applyFilterRequired = ( item, error ) => {

		// テキスト
		if ( ! item.value ) {
			error.push( item.name + ' が記入されておりません。' );
			item.classList.add( 'js__error--field' );

		// チェックボックス
		} else if ( item.type === 'checkbox' && item.checked === false ) {
			error.push( item.name + ' がチェックされていません。' );
			item.classList.add( 'js__error--field' );

		}
		return error;
	};

	// メールアドレスの確認
	const applyFilterMailAddress = ( item, error ) => {
		if ( item.classList.contains( 'js__error--field' ) || item.type !== 'email' ) {
			return error;
		}

		if ( ! item.value.match( /.+@.+\..+/g ) ) {
			error.push( 'メールアドレスの形式が異なります。' );
			item.classList.add( 'js__error--field' );
		}
		return error;
	};

	// 電話番号の確認
	const applyFilterPhoneNumber = ( item, error ) => {
		if ( item.classList.contains( 'js__error--field' ) || item.type !== 'tel' ) {
			return error;
		}

		if ( ! item.value.match( /^[0-9|０-９|ー|-]+$/ ) ) {
			error.push( item.name + ' の形式が異なります。' );
			item.classList.add( 'js__error--field' );
		}
		return error;
	};

	// 数値の確認
	const applyFilterInteger = ( item, error ) => {
		if ( item.classList.contains( 'js__error--field' ) || item.type !== 'number' ) {
			return error;
		}

		if ( isNaN( item.value ) ) {
			error.push( item.name + ' は数値のみ入力してください。' );
			item.classList.add( 'js__error--field' );
		}
		return error;
	};

	// エラーの自動非表示
	let timer = 0;
	const removeError = () => {
		if ( 0 < timer ) {
			clearTimeout( timer );
		}
		timer = setTimeout( () => {
			$messageError.style.opacity = 0;
		}, 4000 );
	};
};
export default VerifySubmit;
