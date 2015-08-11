try {
	sessionStorage.setItem('TYPO3.Neos.lastVisitedNode', document.querySelector('script[data-neos-node]').getAttribute('data-neos-node'));
} catch(e) {}