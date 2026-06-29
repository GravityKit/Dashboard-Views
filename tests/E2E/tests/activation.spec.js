const { test, expect } = require('@playwright/test');

test.describe('Dashboard Views — Activation Smoke Test', () => {

	test('Plugin activates without fatal PHP errors', async ({ page }) => {
		await page.goto('/wp-admin/plugins.php');

		// Fatal error banner shown by WordPress Site Health when a plugin crashes on load.
		// This is the primary signal for the vendor_prefixed/Action Scheduler class-of-bug.
		await expect(
			page.getByText('The site is experiencing technical difficulties')
		).not.toBeVisible();

		// "Deactivate" link present means the plugin loaded successfully and is active.
		await expect(
			page.locator('[data-plugin*="gravityview-dashboard-views.php"] .deactivate a')
		).toBeVisible();
	});

	test('WordPress admin dashboard loads cleanly after activation', async ({ page }) => {
		await page.goto('/wp-admin/');

		await expect(
			page.getByText('The site is experiencing technical difficulties')
		).not.toBeVisible();

		// Admin bar presence confirms WordPress bootstrapped fully — all plugins_loaded hooks ran.
		await expect(page.locator('#wpadminbar')).toBeVisible();
	});

	test('Gravity Forms menu loads without errors', async ({ page }) => {
		// A GravityForms admin page exercises the full Dashboard Views init path including
		// any hooks registered at plugins_loaded.
		await page.goto('/wp-admin/admin.php?page=gf_edit_forms');

		await expect(
			page.getByText('The site is experiencing technical difficulties')
		).not.toBeVisible();

		await expect(page.locator('#wpbody')).toBeVisible();
	});

	test('product is listed as active in GravityKit licenses', async ({ page }) => {
		await page.goto('/wp-admin/admin.php?page=gk_licenses&filter=active');

		const productCard = page.locator('.sections .grid > div').filter({
			has: page.locator('h3', { hasText: 'Dashboard Views' }),
		});

		await expect(productCard).toBeVisible();
		await expect(productCard.locator('button[role="switch"][aria-checked="true"]')).toBeVisible();
	});

	test('shows dependency dialog when attempting to deactivate GravityView', async ({ page }) => {
		let dialogMessage = '';

		page.on('dialog', async (dialog) => {
			dialogMessage = dialog.message();
			await dialog.accept();
		});

		await page.goto('/wp-admin/plugins.php');

		const deactivateLink = page.locator('[data-plugin*="gravityview.php"] .deactivate a');
		await expect(deactivateLink).toBeVisible();
		await deactivateLink.click();
		await page.waitForLoadState('networkidle');

		expect(dialogMessage).toContain('GravityView is required by');

		// Re-activate GravityView so subsequent tests are not affected.
		await page.goto('/wp-admin/plugins.php');
		const activateLink = page.locator('[data-plugin*="gravityview.php"] .activate a');

		if (await activateLink.isVisible()) {
			await activateLink.click();
			await page.waitForLoadState('networkidle');
		}
	});

});
