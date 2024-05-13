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

	if ( !document.querySelector( userRolesSelectElementId ) || !dashboardViewsEnableEl ) {
		return;
	}

	const showEnableDashboardViewsFieldSetting = () => {
		const settingEls = document.querySelectorAll( '.gv-setting-container-dashboard_views_show_field' );

		settingEls.forEach( ( el ) => el.style.display = dashboardViewsEnableEl.checked ? 'block' : 'none' );
	};

	new TomSelect( userRolesSelectElementId );

	showEnableDashboardViewsFieldSetting();

	dashboardViewsEnableEl.addEventListener( 'change', showEnableDashboardViewsFieldSetting );
} );
