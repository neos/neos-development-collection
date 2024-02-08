import React, { ReactNode } from 'react';

interface ErrorBoundaryState {
    hasError: boolean;
    error: Error | null;
}

interface ErrorBoundaryProps {
    children: ReactNode;
}

export default class ErrorBoundary extends React.Component<ErrorBoundaryProps, ErrorBoundaryState> {
    constructor(props) {
        super(props);
        this.state = { hasError: false, error: null };
    }

    static getDerivedStateFromError() {
        return { hasError: true };
    }

    componentDidCatch(error, errorInfo) {
        this.setState({ error });
        console.error(error, errorInfo);
    }

    render() {
        const { hasError, error } = this.state;

        if (hasError) {
            return (
                <div>
                    <h1>Something went wrong and the following error occurred:</h1>
                    <br />
                    <pre>{error?.message}</pre>
                </div>
            );
        }

        return this.props.children;
    }
}
