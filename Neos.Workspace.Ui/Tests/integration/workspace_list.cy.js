describe('Workspace list', () => {
    it('Should display a table with rows for each workspace', () => {
        cy.visit('/');
        cy.get('#workspaceTable');
        cy.get('#workspace-user-sskinner').then(($row) => {
            expect($row.text()).contains('Sidney Skinner');
        });
    });

    it('Should display open the workspace creation dialog', () => {
        cy.visit('/');
        cy.get('#createButton').click();
        cy.get('#createWorkspaceDialog')
            .as('createWorkspaceDialog')
            .then(($dialog) => {
                expect($dialog.text()).contains('Create new workspace');
                cy.get('@createWorkspaceDialog').find('[name*="title"]').type('My new workspace');
                cy.get('@createWorkspaceDialog').get('[name*="description"]').type('This is a great workspace');
                cy.get('@createWorkspaceDialog').get('#createWorkspaceDialogCreate').click();
                expect(!$dialog);
            });
    });
});
