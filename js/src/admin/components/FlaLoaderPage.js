import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Select from 'flarum/common/components/Select';

export default class FlaLoaderPage extends Component {
  oninit(vnode) {
    super.oninit(vnode);
    
    this.loading = true;
    this.files = [];
    this.groups = [];
    this.users = [];
    this.selectedUserId = null;
    this.selectedGroupId = null;
    this.selectedDuration = '30d';
    
    this.loadData();
  }

  loadData() {
    Promise.all([
      app.store.all('groups'),
      app.request({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/fla-loader/files',
      }),
    ]).then(([groups, filesResponse]) => {
      this.groups = groups;
      this.files = filesResponse.data || [];
      this.loading = false;
      m.redraw();
    });
  }

  view() {
    if (this.loading) {
      return <LoadingIndicator />;
    }

    return (
      <div className="FlaLoaderPage">
        <div className="container">
          <h2>Fla Loader Management</h2>

          {/* File Management Section */}
          <div className="FlaLoaderSection">
            <h3>File Management</h3>
            <div className="Form">
              <div className="Form-group">
                <label>Upload File</label>
                <input
                  type="file"
                  onchange={(e) => this.uploadFile(e.target.files[0])}
                />
              </div>
              
              <div className="FileList">
                <h4>Uploaded Files</h4>
                {this.files.length === 0 ? (
                  <p>No files uploaded yet.</p>
                ) : (
                  <table className="Table">
                    <thead>
                      <tr>
                        <th>File Name</th>
                        <th>Public</th>
                        <th>Allowed Groups</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {this.files.map((file) => (
                        <tr key={file.id}>
                          <td>{file.originalName}</td>
                          <td>{file.isPublic ? 'Yes' : 'No'}</td>
                          <td>
                            {file.allowedGroups.length > 0
                              ? file.allowedGroups.join(', ')
                              : 'All'}
                          </td>
                          <td>
                            <Button
                              className="Button Button--danger"
                              onclick={() => this.deleteFile(file.id)}
                            >
                              Delete
                            </Button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                )}
              </div>
            </div>
          </div>

          {/* Role Assignment Section */}
          <div className="FlaLoaderSection">
            <h3>Time-Limited Role Assignment</h3>
            <div className="Form">
              <div className="Form-group">
                <label>User ID</label>
                <input
                  type="number"
                  className="FormControl"
                  value={this.selectedUserId || ''}
                  oninput={(e) => { this.selectedUserId = e.target.value; }}
                  placeholder="Enter user ID"
                />
              </div>

              <div className="Form-group">
                <label>Group</label>
                <Select
                  value={this.selectedGroupId}
                  options={this.groups.reduce((acc, group) => {
                    acc[group.id()] = group.nameSingular();
                    return acc;
                  }, {})}
                  onchange={(value) => { this.selectedGroupId = value; }}
                />
              </div>

              <div className="Form-group">
                <label>Duration</label>
                <Select
                  value={this.selectedDuration}
                  options={{
                    '7d': '7 Days',
                    '30d': '30 Days',
                    '180d': '180 Days',
                    '1y': '1 Year',
                    'lifetime': 'Lifetime',
                  }}
                  onchange={(value) => { this.selectedDuration = value; }}
                />
              </div>

              <Button
                className="Button Button--primary"
                onclick={() => this.assignRole()}
              >
                Assign Role
              </Button>
            </div>
          </div>
        </div>
      </div>
    );
  }

  uploadFile(file) {
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);
    formData.append('isPublic', false);
    formData.append('allowedGroups', JSON.stringify([]));

    app.request({
      method: 'POST',
      url: app.forum.attribute('apiUrl') + '/fla-loader/files',
      body: formData,
      serialize: (raw) => raw,
    }).then(() => {
      app.alerts.show({ type: 'success' }, 'File uploaded successfully');
      this.loadData();
    }).catch(() => {
      app.alerts.show({ type: 'error' }, 'Failed to upload file');
    });
  }

  deleteFile(fileId) {
    if (!confirm('Are you sure you want to delete this file?')) return;

    app.request({
      method: 'DELETE',
      url: app.forum.attribute('apiUrl') + '/fla-loader/files/' + fileId,
    }).then(() => {
      app.alerts.show({ type: 'success' }, 'File deleted successfully');
      this.loadData();
    }).catch(() => {
      app.alerts.show({ type: 'error' }, 'Failed to delete file');
    });
  }

  assignRole() {
    if (!this.selectedUserId || !this.selectedGroupId) {
      app.alerts.show({ type: 'error' }, 'Please fill in all fields');
      return;
    }

    app.request({
      method: 'POST',
      url: app.forum.attribute('apiUrl') + '/fla-loader/roles',
      body: {
        userId: this.selectedUserId,
        groupId: this.selectedGroupId,
        duration: this.selectedDuration,
      },
    }).then(() => {
      app.alerts.show({ type: 'success' }, 'Role assigned successfully');
      this.selectedUserId = null;
      this.selectedGroupId = null;
      this.selectedDuration = '30d';
      m.redraw();
    }).catch(() => {
      app.alerts.show({ type: 'error' }, 'Failed to assign role');
    });
  }
}
