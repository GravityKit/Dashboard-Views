import TomSelect from 'tom-select';

document.addEventListener( 'DOMContentLoaded', function () {
	const settingsWrapperEl = document.getElementById( 'gravityview_settings' );

	if ( !settingsWrapperEl || settingsWrapperEl.getAttribute( 'data-loaded' ) === 'true' ) {
		return;
	}

	settingsWrapperEl.setAttribute( 'data-loaded', 'true' );

	const userRolesSelectElementId = '#gravityview_se_dashboard_views_user_roles';
	const dashboardViewsEnableEl = document.getElementById( 'gravityview_se_dashboard_views_enable' );
	const viewTitleEl = document.querySelector( '.wp-heading-inline' );
	const disableFrontendDisplayEl = document.getElementById( 'gravityview_se_dashboard_views_show_in_frontend' );

	function showFieldOptions() {
		const settingEls = document.querySelectorAll( '.gv-setting-container-dashboard_views_show_field, .gv-setting-container-dashboard_views_exclude_from_frontend' );

		settingEls.forEach( ( el ) => el.style.display = dashboardViewsEnableEl.checked ? 'block' : 'none' );

		// Hide Visibility section if all nested DIVs are hidden.
		// For example, when only Dashboard Views-related inputs are present and the View is not configured for display in the Dashboard).
		document.querySelectorAll( 'fieldset.item-settings-group-visibility' ).forEach( fieldset => {
			const divs = fieldset.querySelectorAll( 'div' );

			const allDivsHidden = Array.from( divs ).every( div => div.style.display === 'none' || div.hidden );

			fieldset.style.display = allDivsHidden ? 'none' : '';
		} );
	}

	function showFrontendDisplayDisabledNotice() {
		const noticeElementId = 'frontend_display_disabled_notice';

		if ( !disableFrontendDisplayEl.checked ) {
			viewTitleEl.insertAdjacentHTML(
				'afterend',
				`<div class="notice notice-warning" id="${ noticeElementId }">
					<p>${ window.gkDashboardViews?.frontend_display_disabled_notice }</p>
				</div>` );

			return;
		}

		const existingNotice = document.getElementById( noticeElementId );

		if ( existingNotice ) {
			existingNotice.remove();
		}
	}

	new TomSelect( userRolesSelectElementId );

	showFieldOptions();
	showFrontendDisplayDisabledNotice();

	dashboardViewsEnableEl.addEventListener( 'change', showFieldOptions );
	disableFrontendDisplayEl.addEventListener( 'change', showFrontendDisplayDisabledNotice );
} );
