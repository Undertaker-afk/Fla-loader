import app from 'flarum/admin/app';
import FlaLoaderPage from './components/FlaLoaderPage';

app.initializers.add('undertaker/fla-loader', () => {
  app.extensionData
    .for('undertaker/fla-loader')
    .registerSetting({
      setting: 'fla-loader.public_file_id',
      type: 'text',
      label: app.translator.trans('undertaker-fla-loader.admin.settings.public_file_id_label'),
      help: app.translator.trans('undertaker-fla-loader.admin.settings.public_file_id_help'),
    })
    .registerPermission(
      {
        icon: 'fas fa-download',
        label: app.translator.trans('undertaker-fla-loader.admin.permissions.download_files'),
        permission: 'flaLoader.downloadFiles',
      },
      'view'
    )
    .registerPermission(
      {
        icon: 'fas fa-upload',
        label: app.translator.trans('undertaker-fla-loader.admin.permissions.manage_files'),
        permission: 'flaLoader.manageFiles',
      },
      'moderate'
    )
    .registerPage({
      component: FlaLoaderPage,
      path: '/fla-loader',
    });
});
