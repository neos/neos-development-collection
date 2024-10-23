
window.addEventListener('DOMContentLoaded', (event) => {
	document.body.addEventListener('htmx:afterOnLoad', function (evt) {
		const input = document.getElementById("check-all");

		// Attach event listener after input is loaded
		if (input) {
			input.addEventListener(
				'change', function (event) {
					document.getElementById('batch-actions').classList.toggle('neos-hidden');
					document.getElementById('all-actions').classList.toggle('neos-hidden');
					for (const checkbox of document.querySelectorAll('tbody input[type="checkbox"]')) {
						checkbox.checked = input.checked;
					}
				}
			)
			for (const checkbox of document.querySelectorAll('tbody input[type="checkbox"]')) {
				checkbox.addEventListener( 'change', function(){
					if(!checkbox.checked){
						input.checked = false;
					}
					if(document.querySelectorAll('tbody input[type="checkbox"]:checked').length === 0){
						document.getElementById('batch-actions').classList.add('neos-hidden');
						document.getElementById('all-actions').classList.remove('neos-hidden');
					}
				});

			}
			for (const toggleDocument of document.querySelectorAll('.fold-toggle')) {
				toggleDocument.addEventListener( 'click', function(){

					toggleDocument.classList.toggle('fa-chevron-down');
					toggleDocument.classList.toggle('fa-chevron-up');

					let nextElement = toggleDocument.closest('.neos-document').nextElementSibling;
					do{
						nextElement.classList.toggle('neos-hidden')
						nextElement = nextElement.nextElementSibling;
					}
					while (nextElement && !nextElement.classList.contains('neos-document'))
				});

			}
			document.getElementById('collapse-all').addEventListener(
				'click', function (event) {
					const collapseButton = document.getElementById('collapse-all');
					let status = (collapseButton.dataset.toggled === 'true');
					console.log(status);
					if(status){
						for (const toggle of document.querySelectorAll('.fold-toggle')) {
							toggle.classList.remove('fa-chevron-down');
							toggle.classList.add('fa-chevron-up');
						}
						for (const change of document.querySelectorAll('.neos-change')) {
							change.classList.add('neos-hidden');
						}

					} else {
						for (const toggle of document.querySelectorAll('.fold-toggle')) {
							toggle.classList.add('fa-chevron-down');
							toggle.classList.remove('fa-chevron-up');
						}
						for (const change of document.querySelectorAll('.neos-change')) {
							change.classList.remove('neos-hidden');
						}
					}

					collapseButton.childNodes[0].classList.toggle('fa-up-right-and-down-left-from-center');
					collapseButton.childNodes[0].classList.toggle('fa-down-left-and-up-right-to-center')
					collapseButton.dataset.toggled = !status;

				}
			)

		}
	});
});
