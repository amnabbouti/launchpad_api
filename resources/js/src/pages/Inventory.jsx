import * as React from 'react';
import DashboardLayout from '../layouts/DashboardLayout';
import { Plus } from 'lucide-react';
import { Button } from '../ui/Button';
import { Tabs, TabsList, TabsTrigger } from '../ui/Tabs';
import InventoryState from '../inventory/InventoryState';
import InventoryTable from '../inventory/InventoryTable';
import RecentActivity from '../inventory/RecentActivity';
import CategoryManagement from '../inventory/CategoryManagement';
import LocationManagement from '../inventory/Locations';

function InventoryPage({
  items = [],
  stats = { total: 0, inStock: 0, lowStock: 0, outOfStock: 0, categories: [] },
  activities = [],
}) {
  const [activeTab, setActiveTab] = React.useState('items');

  return (
    <div className="bg-black text-gray-100 p-4">
      <div className="flex items-center justify-between mb-4">
        <h2 className="text-xl font-bold">Inventory Management</h2>
        <div className="flex items-center space-x-2">
          <Button
            variant="outline"
            size="sm"
            className="text-xs bg-neutral-900 border-neutral-800"
          >
            Export
          </Button>
          <Button size="sm" className="text-xs bg-blue-600 hover:bg-blue-700">
            <Plus className="h-3.5 w-3.5 mr-1" />
            Add Item
          </Button>
        </div>
      </div>

      {/* Inventory Stats */}
      <InventoryState stats={stats} />

      {/* Tabs */}
      <Tabs value={activeTab} onValueChange={setActiveTab} className="mb-4">
        <TabsList className="bg-neutral-900 border border-neutral-800">
          <TabsTrigger
            value="items"
            className="data-[state=active]:bg-blue-600 data-[state=active]:text-white"
          >
            Items
          </TabsTrigger>
          <TabsTrigger
            value="categories"
            className="data-[state=active]:bg-blue-600 data-[state=active]:text-white"
          >
            Categories
          </TabsTrigger>
          <TabsTrigger
            value="locations"
            className="data-[state=active]:bg-blue-600 data-[state=active]:text-white"
          >
            Locations
          </TabsTrigger>
        </TabsList>
      </Tabs>

      {/* Conditional Rendering Based on Active Tab */}
      {activeTab === 'items' && (
        <div className="mb-4">
          <InventoryTable items={items} />
        </div>
      )}
      {activeTab === 'categories' && (
        <div className="mb-4">
          <CategoryManagement categories={stats?.categories || []} />
        </div>
      )}
      {activeTab === 'locations' && (
        <div className="mb-4">
          <LocationManagement />
        </div>
      )}

      {/* Analytics Section */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div className="bg-neutral-900 border border-neutral-800 rounded-lg overflow-hidden">
          <div className="px-4 py-3 bg-neutral-900 border-b border-neutral-800">
            <h3 className="text-base font-mono text-white">
              INVENTORY ANALYTICS
            </h3>
          </div>
          <div className="p-4 grid grid-cols-1 gap-4">
            <RecentActivity activities={activities} />
          </div>
        </div>

        <div className="bg-neutral-900 border border-neutral-800 rounded-lg overflow-hidden">
          <div className="px-4 py-3 bg-neutral-900 border-b border-neutral-800">
            <h3 className="text-base font-mono text-white">SYSTEM STATUS</h3>
          </div>
          <div className="p-4">
            <p className="text-gray-400 text-sm">System is operational</p>
          </div>
        </div>
      </div>
    </div>
  );
}

InventoryPage.layout = (page) => <DashboardLayout>{page}</DashboardLayout>;

export default InventoryPage;
