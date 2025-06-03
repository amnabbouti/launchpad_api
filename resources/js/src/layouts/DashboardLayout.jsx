import * as React from 'react';
import Sidebar from '../side-bare/SideBare';
import DashboardHeader from '../ui/DashboardHeader';

function DashboardLayout({ children }) {
  return (
    <div className="flex h-screen bg-neutral-950 text-white overflow-hidden">
      {/* Sidebar */}
      <Sidebar />
      
      {/* Main content area */}
      <div className="flex-1 flex flex-col overflow-hidden">
        {/* Header */}
        <DashboardHeader />
        
        {/* Main content */}
        <main className="flex-1 overflow-y-auto p-4">
          {children}
        </main>
      </div>
    </div>
  );
}

// Export the layout component directly
// Pages will use this with the Inertia.js layout pattern: PageComponent.layout = page => <DashboardLayout>{page}</DashboardLayout>
export default DashboardLayout;
