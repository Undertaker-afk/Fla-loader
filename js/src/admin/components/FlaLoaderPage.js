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
    
    // File upload state
    this.uploadFileIsPublic = false;
    this.uploadFileGroups = [];
    this.fileInputRef = null;
    this.hwidUserIdRef = null;
    
    this.loadData();
  }
  
  oncreate(vnode) {
    super.oncreate(vnode);
    this.element = vnode.dom;
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
                  oncreate={(vnode) => { this.fileInputRef = vnode.dom; }}
                />
              </div>
              
              <div className="Form-group">
                <label>
                  <input
                    type="checkbox"
                    checked={this.uploadFileIsPublic}
                    onchange={(e) => { this.uploadFileIsPublic = e.target.checked; }}
                  />
                  {' '}Make file public (visible in Download page)
                </label>
              </div>
              
              <div className="Form-group">
                <label>Allowed Groups (hold Ctrl/Cmd to select multiple)</label>
                <select
                  multiple
                  className="FormControl"
                  style="height: 120px;"
                  onchange={(e) => {
                    this.uploadFileGroups = Array.from(e.target.selectedOptions).map(o => parseInt(o.value));
                  }}
                >
                  {this.groups.map((group) => (
                    <option key={group.id()} value={group.id()}>
                      {group.nameSingular()}
                    </option>
                  ))}
                </select>
                <small>Leave empty to allow all groups</small>
              </div>
              
              <Button
                className="Button Button--primary"
                onclick={() => this.uploadFile()}
              >
                Upload File
              </Button>
              
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

          {/* HWID Management Section */}
          <div className="FlaLoaderSection">
            <h3>HWID Management</h3>
            <div className="Form">
              <div className="Form-group">
                <label>User ID</label>
                <input
                  type="number"
                  className="FormControl"
                  oncreate={(vnode) => { this.hwidUserIdRef = vnode.dom; }}
                  placeholder="Enter user ID to check or reset HWID"
                />
              </div>

              <div style="display: flex; gap: 10px;">
                <Button
                  className="Button Button--primary"
                  onclick={() => this.checkHwid()}
                >
                  Check HWID Status
                </Button>
                
                <Button
                  className="Button Button--warning"
                  onclick={() => this.resetHwid()}
                >
                  Reset HWID
                </Button>
              </div>

              <div id="hwidStatus" style="margin-top: 15px;"></div>
            </div>
          </div>
        </div>
      </div>
    );
  }

  uploadFile() {
    const file = this.fileInputRef && this.fileInputRef.files[0];
    
    if (!file) {
      app.alerts.show({ type: 'error' }, 'Please select a file to upload');
      return;
    }

    const formData = new FormData();
    formData.append('file', file);
    formData.append('isPublic', this.uploadFileIsPublic);
    formData.append('allowedGroups', JSON.stringify(this.uploadFileGroups));

    app.request({
      method: 'POST',
      url: app.forum.attribute('apiUrl') + '/fla-loader/files',
      body: formData,
      serialize: (raw) => raw,
    }).then(() => {
      app.alerts.show({ type: 'success' }, 'File uploaded successfully');
      // Reset form
      if (this.fileInputRef) this.fileInputRef.value = '';
      this.uploadFileIsPublic = false;
      this.uploadFileGroups = [];
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

  checkHwid() {
    const userId = this.hwidUserIdRef ? this.hwidUserIdRef.value : null;

    if (!userId) {
      app.alerts.show({ type: 'error' }, 'Please enter a user ID');
      return;
    }

    app.request({
      method: 'GET',
      url: app.forum.attribute('apiUrl') + '/fla-loader/hwid/' + userId,
    }).then((response) => {
      const data = response.data;
      const statusDiv = this.element && this.element.querySelector('#hwidStatus');
      
      if (statusDiv) {
        statusDiv.innerHTML = `
          <div class="Alert Alert--info">
            <strong>User:</strong> ${data.username}<br>
            <strong>Has HWID:</strong> ${data.hasHwid ? 'Yes' : 'No'}<br>
            ${data.hasHwid ? `<strong>HWID Preview:</strong> ${data.hwid}<br>` : ''}
            ${data.registeredAt ? `<strong>Registered At:</strong> ${data.registeredAt}` : ''}
          </div>
        `;
      }
      
      app.alerts.show({ type: 'success' }, 'HWID status retrieved');
    }).catch((error) => {
      app.alerts.show({ type: 'error' }, 'Failed to get HWID status');
    });
  }

  resetHwid() {
    const userId = this.hwidUserIdRef ? this.hwidUserIdRef.value : null;

    if (!userId) {
      app.alerts.show({ type: 'error' }, 'Please enter a user ID');
      return;
    }

    if (!confirm('Are you sure you want to reset this user\'s HWID? They will be able to login from a new device.')) {
      return;
    }

    app.request({
      method: 'POST',
      url: app.forum.attribute('apiUrl') + '/fla-loader/hwid/reset',
      body: { userId: userId },
    }).then((response) => {
      const data = response.data;
      app.alerts.show({ type: 'success' }, data.message);
      
      // Clear the status display
      const statusDiv = this.element && this.element.querySelector('#hwidStatus');
      if (statusDiv) {
        statusDiv.innerHTML = '';
      }
      
      // Clear the input
      if (this.hwidUserIdRef) {
        this.hwidUserIdRef.value = '';
      }
    }).catch(() => {
      app.alerts.show({ type: 'error' }, 'Failed to reset HWID');
    });
  }
}
