/**
 * PSBDx Smart Report Management — Public Scripts
 *
 * Handles modal open/close, "Other" reason toggling,
 * and AJAX form submission with loading/success/error states.
 *
 * Relies on `psbdxSrm` global localised by wp_localize_script().
 *
 * @package PSBDx_Smart_Report_Management
 * @since   1.0.0
 */

/* global psbdxSrm, fetch */
( function () {
	'use strict';

	// ── Helpers ────────────────────────────────────────────────────────────

	/**
	 * Retrieve a modal element by its trigger's data-target attribute.
	 *
	 * @param  {HTMLElement} trigger  The trigger button.
	 * @return {HTMLElement|null}
	 */
	function getModal( trigger ) {
		var id = trigger.getAttribute( 'data-target' );
		return id ? document.getElementById( id ) : null;
	}

	/**
	 * Open a modal — adds class and locks body scroll.
	 *
	 * @param {HTMLElement} modal
	 */
	function openModal( modal ) {
		modal.classList.add( 'psbdx-is-open' );
		document.body.style.overflow = 'hidden';

		// Move focus into the modal for accessibility.
		var firstFocusable = modal.querySelector( 'button, input, select, textarea, [tabindex]:not([tabindex="-1"])' );
		if ( firstFocusable ) {
			firstFocusable.focus();
		}
	}

	/**
	 * Close a modal — removes class and restores body scroll.
	 *
	 * @param {HTMLElement} modal
	 */
	function closeModal( modal ) {
		modal.classList.remove( 'psbdx-is-open' );
		document.body.style.overflow = '';
	}

	/**
	 * Close all open modals.
	 */
	function closeAllModals() {
		document.querySelectorAll( '.psbdx-modal.psbdx-is-open' ).forEach( closeModal );
	}

	// ── Modal open/close ───────────────────────────────────────────────────

	/**
	 * Handle all click events via event delegation.
	 *
	 * @param {MouseEvent} e
	 */
	document.body.addEventListener( 'click', function ( e ) {
		// Open modal.
		var trigger = e.target.closest( '.psbdx-trigger-btn' );
		if ( trigger ) {
			e.preventDefault();
			e.stopPropagation();
			var modal = getModal( trigger );
			if ( modal ) {
				openModal( modal );
			}
			return;
		}

		// Close via X button.
		var closeBtn = e.target.closest( '.psbdx-modal-close' );
		if ( closeBtn ) {
			var parentModal = closeBtn.closest( '.psbdx-modal' );
			if ( parentModal ) {
				closeModal( parentModal );
			}
			return;
		}

		// Close by clicking the backdrop (outside the panel).
		if ( e.target.classList.contains( 'psbdx-modal' ) ) {
			closeModal( e.target );
		}
	} );

	// Close on Escape key.
	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' ) {
			closeAllModals();
		}
	} );

	// ── "Other" reason field toggle ────────────────────────────────────────

	document.body.addEventListener( 'change', function ( e ) {
		if ( ! e.target.classList.contains( 'psbdx-reason-select' ) ) {
			return;
		}

		var form        = e.target.closest( 'form' );
		var otherField  = form ? form.querySelector( '.psbdx-other-reason' ) : null;
		var otherInput  = otherField ? otherField.querySelector( 'input' ) : null;

		if ( ! otherField || ! otherInput ) {
			return;
		}

		var i18nOther = ( window.psbdxSrm && psbdxSrm.i18n && psbdxSrm.i18n.otherReason ) ? psbdxSrm.i18n.otherReason : 'Other';

		if ( e.target.value === i18nOther ) {
			otherField.style.display = 'block';
			otherInput.setAttribute( 'required', 'required' );
			otherInput.focus();
		} else {
			otherField.style.display = 'none';
			otherInput.removeAttribute( 'required' );
			otherInput.value = '';
		}
	} );

	// ── Form submission ────────────────────────────────────────────────────

	document.body.addEventListener( 'submit', function ( e ) {
		if ( ! e.target.classList.contains( 'psbdx-report-form' ) ) {
			return;
		}

		e.preventDefault();
		handleSubmit( e.target );
	} );

	/**
	 * Handle AJAX form submission.
	 *
	 * @param {HTMLFormElement} form
	 */
	function handleSubmit( form ) {
		var modal     = form.closest( '.psbdx-modal' );
		var response  = modal ? modal.querySelector( '.psbdx-form-response' ) : null;
		var submitBtn = form.querySelector( '.psbdx-submit-btn' );
		var btnLabel  = submitBtn ? submitBtn.querySelector( '.psbdx-btn-label' )  : null;
		var btnSpin   = submitBtn ? submitBtn.querySelector( '.psbdx-btn-spinner' ) : null;

		var i18n = ( window.psbdxSrm && psbdxSrm.i18n ) ? psbdxSrm.i18n : {};

		// Show loading state.
		if ( submitBtn ) { submitBtn.disabled = true; }
		if ( btnLabel )  { btnLabel.style.display  = 'none'; }
		if ( btnSpin )   { btnSpin.style.display    = 'flex'; }
		if ( response )  { response.innerHTML = ''; }

		var formData = new FormData( form );
		var ajaxUrl  = ( window.psbdxSrm && psbdxSrm.ajaxUrl ) ? psbdxSrm.ajaxUrl : '/wp-admin/admin-ajax.php';

		fetch( ajaxUrl, {
			method:      'POST',
			body:        formData,
			credentials: 'same-origin',
		} )
		.then( function ( res ) {
			if ( ! res.ok ) {
				throw new Error( 'HTTP ' + res.status );
			}
			return res.json();
		} )
		.then( function ( data ) {
			if ( data.success ) {
				handleSuccess( form, response, modal );
			} else {
				var msg = ( data.data && typeof data.data === 'string' )
					? data.data
					: ( i18n.error || 'Submission failed. Please try again.' );
				handleError( response, msg, submitBtn, btnLabel, btnSpin );
			}
		} )
		.catch( function () {
			handleError(
				response,
				i18n.networkError || 'Network error. Please check your connection.',
				submitBtn,
				btnLabel,
				btnSpin
			);
		} );
	}

	/**
	 * Show the success state and auto-close the modal after 3 seconds.
	 *
	 * @param {HTMLFormElement} form
	 * @param {HTMLElement}     response
	 * @param {HTMLElement}     modal
	 */
	function handleSuccess( form, response, modal ) {
		var i18n = ( window.psbdxSrm && psbdxSrm.i18n ) ? psbdxSrm.i18n : {};

		form.style.display = 'none';

		if ( response ) {
			response.innerHTML =
				'<div class="psbdx-success">' +
					'<div class="psbdx-success-icon">&#x2705;</div>' +
					'<h3>' + escapeHtml( i18n.submitted  || 'Report Submitted!'                        ) + '</h3>' +
					'<p>'  + escapeHtml( i18n.thankyou   || 'Thank you. We will get back to you soon.' ) + '</p>' +
				'</div>';
		}

		// Auto-close and reset after 3 seconds.
		setTimeout( function () {
			if ( modal ) {
				closeModal( modal );
			}

			// Reset form state.
			form.reset();
			form.style.display = '';

			var submitBtn = form.querySelector( '.psbdx-submit-btn' );
			var btnLabel  = submitBtn ? submitBtn.querySelector( '.psbdx-btn-label' )  : null;
			var btnSpin   = submitBtn ? submitBtn.querySelector( '.psbdx-btn-spinner' ) : null;
			if ( submitBtn ) { submitBtn.disabled = false; }
			if ( btnLabel )  { btnLabel.style.display  = ''; }
			if ( btnSpin )   { btnSpin.style.display    = 'none'; }

			if ( response ) { response.innerHTML = ''; }

			// Reload the page if a user report table is present so it updates.
			if ( document.querySelector( '.psbdx-history-wrap' ) ) {
				window.location.reload();
			}
		}, 3000 );
	}

	/**
	 * Show an error notice and restore the submit button.
	 *
	 * @param {HTMLElement}     response
	 * @param {string}          message
	 * @param {HTMLElement}     submitBtn
	 * @param {HTMLElement}     btnLabel
	 * @param {HTMLElement}     btnSpin
	 */
	function handleError( response, message, submitBtn, btnLabel, btnSpin ) {
		if ( response ) {
			response.innerHTML =
				'<div class="psbdx-notice psbdx-notice-error">' + escapeHtml( message ) + '</div>';
		}

		if ( submitBtn ) { submitBtn.disabled = false; }
		if ( btnLabel )  { btnLabel.style.display  = ''; }
		if ( btnSpin )   { btnSpin.style.display    = 'none'; }
	}

	/**
	 * Escape HTML special characters in a string.
	 *
	 * @param  {string} str
	 * @return {string}
	 */
	function escapeHtml( str ) {
		return String( str )
			.replace( /&/g,  '&amp;'  )
			.replace( /</g,  '&lt;'   )
			.replace( />/g,  '&gt;'   )
			.replace( /"/g,  '&quot;' )
			.replace( /'/g,  '&#039;' );
	}

}() );
