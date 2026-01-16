import Page from 'flarum/common/components/Page';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Button from 'flarum/common/components/Button';

export default class DownloadPage extends Page {
  oninit(vnode) {
    super.oninit(vnode);
    
    this.loading = true;
    this.files = [];
    
    this.loadFiles();
  }

  loadFiles() {
    // For now, we'll fetch files that are marked as public
    app.request({
      method: 'GET',
      url: app.forum.attribute('apiUrl') + '/fla-loader/files',
    }).then((response) => {
      // Filter to only show files user has access to
      this.files = (response.data || []).filter(file => file.isPublic);
      this.loading = false;
      m.redraw();
    }).catch(() => {
      this.loading = false;
      m.redraw();
    });
  }

  view() {
    return (
      <div className="DownloadPage">
        <div className="container">
          <h2>Downloads</h2>
          
          {this.loading ? (
            <LoadingIndicator />
          ) : this.files.length === 0 ? (
            <p>No files available for download.</p>
          ) : (
            <div className="FilesList">
              {this.files.map((file) => (
                <div key={file.id} className="FileItem">
                  <div className="FileInfo">
                    <h3>{file.originalName}</h3>
                    <p>Size: {this.formatSize(file.size)}</p>
                  </div>
                  <Button
                    className="Button Button--primary"
                    onclick={() => this.downloadFile(file.id, file.originalName)}
                  >
                    Download
                  </Button>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    );
  }

  downloadFile(fileId, filename) {
    window.location.href = app.forum.attribute('apiUrl') + '/fla-loader/download/' + fileId;
  }

  formatSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
  }
}
