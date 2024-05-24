// File: src/js/dropdown.js
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

	function showEnableDashboardViewsFieldSetting() {
		const settingEls = document.querySelectorAll( '.gv-setting-container-dashboard_views_show_field' );

		settingEls.forEach( ( el ) => el.style.display = dashboardViewsEnableEl.checked ? 'block' : 'none' );
	}

	function showFrontendDisplayDisabledNotice() {
		const noticeElementId = 'frontend_display_disabled_notice';

		if ( !disableFrontendDisplayEl.checked ) {
			viewTitleEl.insertAdjacentHTML( 'afterend', `
				<div class="notice notice-warning" id="${ noticeElementId }">
					<p>${ window.gkDashboardViews?.frontend_display_disabled_notice }</p>
				</div>
		` );
		} else {
			const existingNotice = document.getElementById( noticeElementId );

			if ( existingNotice ) {
				existingNotice.remove();
			}
		}
	}

	new TomSelect( userRolesSelectElementId );

	showEnableDashboardViewsFieldSetting();
	showFrontendDisplayDisabledNotice();

	dashboardViewsEnableEl.addEventListener( 'change', showEnableDashboardViewsFieldSetting );
	disableFrontendDisplayEl.addEventListener( 'change', showFrontendDisplayDisabledNotice );
} );
