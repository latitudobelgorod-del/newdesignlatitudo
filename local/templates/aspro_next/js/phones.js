$(() => {
	const $phones = document.querySelectorAll(".phone.with_dropdown");
	$phones.forEach($block => $block.addEventListener("mouseenter", () => {
		const rect = $block.getBoundingClientRect();
		const $dropdown = $block.querySelector(".dropdown");
		if ($dropdown) {
			if (!$dropdown.classList.contains("dropdown--inited")) {
				$dropdown.classList.add("dropdown--inited");
			}

			const dropdown_rect = $dropdown.getBoundingClientRect(),
				dropdown_top = -18,
				dropdown_left = -16;

			if (dropdown_rect.right > document.body.clientWidth) {
				$dropdown.style.left =
					document.body.clientWidth -
					dropdown_rect.right +
					dropdown_left +
					"px";
			}

			if ($block.closest("footer")) {
				const calcHeight = rect.y + dropdown_top + $dropdown.scrollHeight;

				if (calcHeight <= document.body.clientHeight) {
					$dropdown.classList.remove("dropdown--top");
				} else {
					$dropdown.classList.add("dropdown--top");
				}
			}
		}
	}));
});