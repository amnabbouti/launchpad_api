import * as React from 'react';
import DashboardLayout from '../layouts/DashboardLayout';
import ClientManagementPage from './ClientManagement';

function Dashboard({ users = [], systemStatus = {} }) {
  return (
    <ClientManagementPage />
  );
}

Dashboard.layout = page => <DashboardLayout>{page}</DashboardLayout>;

export default Dashboard;
