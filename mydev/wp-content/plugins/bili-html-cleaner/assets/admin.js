document.addEventListener('click', async (event) => {
	const button = event.target.closest('[data-copy-target]');

	if (!button) {
		return;
	}

	const targetId = button.getAttribute('data-copy-target');
	const target = document.getElementById(targetId);

	if (!target) {
		return;
	}

	try {
		await navigator.clipboard.writeText(target.value);
		const originalText = button.textContent;
		button.textContent = '已复制';
		window.setTimeout(() => {
			button.textContent = originalText;
		}, 1200);
	} catch (error) {
		target.focus();
		target.select();
	}
});
