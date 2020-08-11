(function ($) {
	function pdfPreview(wrapper) {
		var pdfDoc = null,
			pageNum = 1,
			pageNumPending = null,
			pageRendering = false;

		PDFJS.workerSrc = '/_Resources/Static/Packages/Neos.Media.Browser/JavaScript/pdf.worker.js';

		var url = $(wrapper).attr('data-asset-preview-url');

		PDFJS.getDocument(url).then(function (pdfDoc_) {
			pdfDoc = pdfDoc_;
			document.getElementById('neos-preview-pdf-page-count').innerHTML = pdfDoc.numPages;
			document.getElementById('neos-preview-pdf-page').innerHTML = pageNum;

			// Initial/first page rendering
			renderPage(pageNum);
		});

		$('#neos-preview-button-previous').bind('click', function (event) {
			event.preventDefault();
			onPreviousPage();
		});

		$('#neos-preview-button-next').bind('click', function (event) {
			event.preventDefault();
			onNextPage();
		});

		function queueRenderPage(num) {
			if (pageRendering) {
				pageNumPending = num;
			} else {
				renderPage(num);
			}
		}

		function onPreviousPage() {
			if (pageNum <= 1) {
				return;
			}
			pageNum--;
			queueRenderPage(pageNum);
		}

		function onNextPage() {
			if (pageNum >= pdfDoc.numPages) {
				return;
			}
			pageNum++;
			queueRenderPage(pageNum);
		}

		function renderPage(num) {
			pageRendering = true;
			// Using promise to fetch the page
			pdfDoc.getPage(num).then(function (page) {
				if (page.getViewport(1.0).width < page.getViewport(1.0).height) {
					var viewport = page.getViewport(wrapper.offsetWidth / page.getViewport(1.0).height);
				} else {
					var viewport = page.getViewport(wrapper.offsetWidth / page.getViewport(1.0).width);
				}

				var canvas = document.getElementById('pdf-canvas');
				var context = canvas.getContext('2d');
				canvas.width = viewport.width;
				canvas.height = viewport.height;
				context.backgroundColor = "141414";

				var renderContext = {
					canvasContext: context,
					viewport: viewport
				};

				var renderTask = page.render(renderContext);
				renderTask.then(function () {
					pageRendering = false;
					if (pageNumPending !== null) {
						// New page rendering is pending
						renderPage(pageNumPending);
						pageNumPending = null;
					}
				});
			});

			document.getElementById('neos-preview-pdf-page').innerHTML = pageNum;
		}
	}
	$(function () {
		var pdfWrapper = document.getElementById('neos-preview-pdf');
		pdfWrapper && pdfPreview(pdfWrapper);

		if (window.parent !== window && window.parent.NeosMediaBrowserCallbacks) {
			$('.neos-action-cancel, .neos-button-primary', '.neos-footer').on('click', function (e) {
				if (window.parent.NeosMediaBrowserCallbacks && typeof window.parent.NeosMediaBrowserCallbacks.close === 'function') {
					window.parent.NeosMediaBrowserCallbacks.close();
				}
			});
		}
	});
})(jQuery);
