import Parcel from '@parcel/core';
import * as express from 'express';
import * as path from 'path';

import { loadFixtures } from './fixtures';

// Setup parcel bundler
const bundler = new Parcel({
    entries: 'index.html',
    targets: {
        default: {
            distDir: './dist',
        },
    },
    serveOptions: {
        port: 3000,
    },
    hmrOptions: {
        port: 3001,
    },
});

bundler.watch().then(() => {
    // Load fixture data
    const { workspaces, changesByWorkspace } = loadFixtures();

    // Setup express server
    const port = 4000;
    const app = express();

    app.use(express.json());
    app.use(express.static(path.join(__dirname, '/public')));
    app.use(express.static(path.join(__dirname, '/dist')));
    app.use(express.static(path.join(__dirname, '../../Resources/Public')));

    app.get('/', (req, res) => {
        res.sendFile(path.join(__dirname, '/dist/index.html'));
    });

    app.get('/getChanges', (req, res) => {
        res.json({
            changesByWorkspace,
        });
    });

    app.get('/showWorkspace', (req, res) => {
        const workspaceName = req.query.workspace.toString();
        const workspace = workspaces[workspaceName];
        res.send(`Viewing changes in workspace "${workspace.title}" is not implemented yet!`);
    });

    app.listen(port, () => {
        console.log(`Success! Your application is running on port ${port}.`);
    });
});
