import addUsersListPane from './addUsersListPane';

app.initializers.add('veroz1-users-list', () => {
    app.extensionSettings['veroz1-users-list'] = () => m.route(app.route('usersList'));

    addUsersListPane();
});
