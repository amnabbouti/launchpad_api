import React from 'react';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import '../css/app.css';

// Initialize Inertia App
console.log('Loading Inertia App...');

createInertiaApp({
  title: (title) => `${title} - Mission Control`,
  resolve: async (name) => {
    console.log('Resolving page:', name);
    
    // Try to dynamically import the page component
    try {
      // This assumes your pages are in the src/pages directory
      const page = await import(`./src/pages/${name}.jsx`);
      return page;
    } catch (error) {
      console.error(`Could not load page ${name}:`, error);
      
      // If we can't find the requested page, redirect to Dashboard as fallback
      try {
        return await import('./src/pages/Dashboard.jsx');
      } catch (fallbackError) {
        console.error('Could not load fallback Dashboard page:', fallbackError);
        throw new Error(`Page ${name} not found and fallback failed`);
      }
    }
  },
  setup({ el, App, props }) {
    console.log('Setting up app with props:', props);
    const root = createRoot(el);
    
    // Properly handle layouts in the render process
    root.render(
      <App
        {...props}
        // This ensures the layout is applied to the page component
        // It follows the pattern described in the project memories
        render={({ Component, props, key }) => {
          // Get the layout component from the page or use null if not defined
          const Layout = Component.layout || (page => page);
          
          return (
            <Layout key={key}>
              <Component {...props} />
            </Layout>
          );
        }}
      />
    );
  },
  progress: {
    color: '#4f46e5',
  },
});
