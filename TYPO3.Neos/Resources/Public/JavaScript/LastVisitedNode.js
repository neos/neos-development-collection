try {
	sessionStorage.setItem('Neos.Neos.lastVisitedNode', document.querySelector('script[data-neos-node]').getAttribute('data-neos-node'));
} catch(e) {}
