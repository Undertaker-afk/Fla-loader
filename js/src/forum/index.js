import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import HeaderSecondary from 'flarum/forum/components/HeaderSecondary';
import LinkButton from 'flarum/common/components/LinkButton';

app.initializers.add('undertaker/fla-loader', () => {
  // Add "Download" link to navbar
  extend(HeaderSecondary.prototype, 'items', function (items) {
    if (app.session.user) {
      items.add(
        'fla-loader-download',
        <LinkButton
          icon="fas fa-download"
          href={app.route('flaLoaderDownload')}
        >
          Download
        </LinkButton>,
        10
      );
    }
  });

  // Register download page route
  app.routes.flaLoaderDownload = {
    path: '/download',
    component: () => import('./components/DownloadPage'),
  };
});
