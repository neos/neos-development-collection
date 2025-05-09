
window.addEventListener('DOMContentLoaded', (event) => {
	document.body.addEventListener('htmx:afterOnLoad', function (evt) {
		initReviewFunctions();
	});
});
window.addEventListener('DOMContentLoaded', (event) => {
	initReviewFunctions();
});
function initReviewFunctions(){
	const input = document.getElementById("check-all");

	// Attach event listener after input is loaded
	if (input) {
		input.addEventListener(
			'change', function (event) {
				for (const checkbox of document.querySelectorAll('tbody input[type="checkbox"]')) {
					checkbox.checked = input.checked;
				}
				if(document.querySelectorAll('tbody input[type="checkbox"]:checked').length === document.querySelectorAll('tbody input[type="checkbox"]').length
				|| document.querySelectorAll('tbody input[type="checkbox"]:checked').length === 0){
					document.getElementById('batch-actions').classList.add('neos-hidden');
					document.getElementById('all-actions').classList.remove('neos-hidden');
				} else {
					document.getElementById('batch-actions').classList.remove('neos-hidden');
					document.getElementById('all-actions').classList.add('neos-hidden');
				}
			}
		)
		for (const checkbox of document.querySelectorAll('tbody input[type="checkbox"]')) {
			checkbox.addEventListener( 'change', function(){
				if(!checkbox.checked){
					input.checked = false;
				}
				if(document.querySelectorAll('tbody input[type="checkbox"]:checked').length === document.querySelectorAll('tbody input[type="checkbox"]').length
					|| document.querySelectorAll('tbody input[type="checkbox"]:checked').length === 0){
					document.getElementById('batch-actions').classList.add('neos-hidden');
					document.getElementById('all-actions').classList.remove('neos-hidden');
				} else {
					document.getElementById('batch-actions').classList.remove('neos-hidden');
					document.getElementById('all-actions').classList.add('neos-hidden');
				}
				const neosDocument = checkbox.closest('.neos-document');

				if(neosDocument.hasAttribute('data-isNew') || neosDocument.hasAttribute('data-isMoved')){
					if(checkbox.checked){
						neosDocument.dataset.documentpath.split('/').forEach(function (parentDocumentId) {
							const parentElement = checkbox.closest('table').querySelector('.neos-document[data-isMoved][data-documentpath$="'+parentDocumentId+'"], .neos-document[data-isNew][data-documentpath$="'+parentDocumentId+'"]');
							if (parentElement !== null) {
								parentElement.querySelector('input').checked = checkbox.checked;
							}
						})
					} else {
						for (const childElement of document.querySelectorAll('.neos-document[data-documentpath^="'+neosDocument.dataset.documentpath+'"]')) {
							childElement.querySelector('input').checked = checkbox.checked
						}
					}
				}
			});

		}
		for (const toggleDocument of document.querySelectorAll('.toggle-document')) {
			toggleDocument.addEventListener( 'click', function(){

				toggleDocument.children[0].classList.toggle('fa-chevron-down');
				toggleDocument.children[0].classList.toggle('fa-chevron-up');

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
				if(status){
					for (const toggle of document.querySelectorAll('.toggle-document')) {
						toggle.children[0].classList.remove('fa-chevron-down');
						toggle.children[0].classList.add('fa-chevron-up');
					}
					for (const change of document.querySelectorAll('.neos-change')) {
						change.classList.add('neos-hidden');
					}

				} else {
					for (const toggle of document.querySelectorAll('.toggle-document')) {
						toggle.children[0].classList.add('fa-chevron-down');
						toggle.children[0].classList.remove('fa-chevron-up');
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
}
