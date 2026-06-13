import React from 'react';
import ReactDOM from 'react-dom/client';
import { createBrowserRouter, RouterProvider } from 'react-router-dom';
const App = React.lazy(() => import('./App'));
import './index.css';

const router = createBrowserRouter([
  { path: '*', element: <App /> },
], {
  future: {
    v7_relativeSplatPath: true,
  },
});

const container = document.getElementById('root')!;
const existingRoot = (window as any).__react_root;
const renderApp = (
  <React.StrictMode>
    <React.Suspense fallback={null}>
      <RouterProvider router={router} future={{ v7_startTransition: true }} />
    </React.Suspense>
  </React.StrictMode>
);

if (existingRoot) {
  existingRoot.render(renderApp);
} else {
  const root = ReactDOM.createRoot(container);
  (window as any).__react_root = root;
  root.render(renderApp);
}
