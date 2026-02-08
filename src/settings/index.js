/**
 * Settings App – Entry Point
 *
 * Mounts the React settings application into the DOM container
 * rendered by the admin page callback.
 *
 * @package Mudrava\ComingSoon
 * @since   1.0.0
 */

import { createRoot } from '@wordpress/element';
import App from './App';
import './style.scss';

const root = document.getElementById('mudrava-coming-soon-root');

if (root) {
    createRoot(root).render(<App />);
}
