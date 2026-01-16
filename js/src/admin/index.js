import app from 'flarum/admin/app';

app.initializers.add('undertaker/fla-loader', () => {
  app.extensionData
    .for('undertaker-fla-loader')
    .registerPage({
      path: '/fla-loader',
      label: 'Fla Loader',
      icon: 'fas fa-download'
    });

  app.routes['flaLoader'] = {
    path: '/fla-loader',
    component: () => import('./components/FlaLoaderPage'),
  };
});
