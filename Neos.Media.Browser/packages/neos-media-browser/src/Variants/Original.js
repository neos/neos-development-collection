import React from 'react';

export default class Original extends React.PureComponent {
    render() {
        const {persistenceIdentifier, previewUri, width, height} = this.props;

        return (
            <ul className="neos-thumbnails">
                <li className="asset" data-asset-identifier={persistenceIdentifier}
                    data-local-asset-identifier={persistenceIdentifier}>
                    <a>
                        <div className="neos-img-container">
                            <img src={previewUri} className="" alt=""/>
                        </div>
                    </a>
                    <div className="neos-img-label">
                            <span className="neos-caption asset-label">
                                <span className="neos-badge neos-pull-right">
                                    <pre>W: {width}px <br/>H: {height}px</pre>
                                </span>
                            </span>
                    </div>
                </li>
            </ul>
        );
    }
}
