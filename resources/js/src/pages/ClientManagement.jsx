import * as React from "react";
import { Link } from "@inertiajs/react";
import { Button } from "../ui/Button";
import DashboardLayout from "../layouts/DashboardLayout";

function ClientManagementPage() {
  // Will be populated with data from API
  const clientModules = [];

  // Sample status data
  const clientStatus = {
    totalClients: "",
    activeNow: "",
    apiUsage: "",
    lastOnboarded: "",
  };

  return (
    <div className="bg-black text-gray-100 dark:bg-black dark:text-gray-100 min-h-screen">
      <div className="p-4">
        {/* Client Status Indicators */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
          <div className="bg-neutral-900 border border-neutral-800 rounded-md p-3">
            <div className="flex items-center justify-between">
              <div className="flex items-center">
                <span className="text-blue-500 mr-2 text-base">👥</span>
                <span className="text-xs text-neutral-400">TOTAL</span>
              </div>
              <span className="text-sm font-mono text-blue-400">
                {clientStatus.totalClients}
              </span>
            </div>
          </div>
          <div className="bg-neutral-900 border border-neutral-800 rounded-md p-3">
            <div className="flex items-center justify-between">
              <div className="flex items-center">
                <span className="text-green-500 mr-2 text-base">⚡</span>
                <span className="text-xs text-neutral-400">ACTIVE</span>
              </div>
              <span className="text-sm font-mono text-green-400">
                {clientStatus.activeNow}
              </span>
            </div>
          </div>
          <div className="bg-neutral-900 border border-neutral-800 rounded-md p-3">
            <div className="flex items-center justify-between">
              <div className="flex items-center">
                <span className="text-purple-500 mr-2 text-base">📊</span>
                <span className="text-xs text-neutral-400">API USAGE</span>
              </div>
              <span className="text-sm font-mono text-purple-400">
                {clientStatus.apiUsage}
              </span>
            </div>
          </div>
          <div className="bg-neutral-900 border border-neutral-800 rounded-md p-3">
            <div className="flex items-center justify-between">
              <div className="flex items-center">
                <span className="text-amber-500 mr-2 text-base">⏰</span>
                <span className="text-xs text-neutral-400">ONBOARDED</span>
              </div>
              <span className="text-sm font-mono text-amber-400">
                {clientStatus.lastOnboarded}
              </span>
            </div>
          </div>
        </div>

        {/* Client Modules Grid */}
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {clientModules.map((module, index) => (
            <div
              key={index}
              className="bg-neutral-900 border border-neutral-800 rounded-lg"
            >
              <div className="p-4">
                <div className="flex items-start">
                  <div className={`p-2 rounded-md border ${module.color}`}>
                    <span className="text-base">{module.icon}</span>
                  </div>
                  <div className="ml-4">
                    <Link href={module.href}>
                      <h3 className="text-base font-mono text-white hover:text-purple-400 dark:hover:text-purple-300 transition-colors">
                        {module.name}
                      </h3>
                    </Link>
                    <p className="mt-1 text-sm text-neutral-400 dark:text-neutral-400">
                      {module.description}
                    </p>
                  </div>
                </div>
              </div>
              <div className="bg-neutral-800 px-4 py-2 rounded-b-lg">
                <div className="text-xs font-mono">
                  <Link href={module.href} className="block">
                    <div className="font-medium text-purple-500 hover:text-purple-400 dark:hover:text-purple-300">
                      &gt; ACCESS MODULE
                    </div>
                  </Link>
                </div>
              </div>
            </div>
          ))}
        </div>

        {/* Client Activity Monitor */}
        <div className="bg-neutral-900 border border-neutral-800 rounded-lg mt-4">
          <div className="px-4 py-4">
            <h3 className="text-base font-mono text-white dark:text-white flex items-center">
              <span className="text-purple-500 mr-2 text-base">🛡️</span>
              CLIENT ACTIVITY MONITOR
            </h3>
            <div className="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-12">
              <div className="lg:col-span-4 border border-neutral-800 bg-neutral-900 rounded-md p-3">
                <h4 className="text-sm font-mono mb-3">TOP CLIENTS</h4>
                <div className="space-y-2">
                  {[
                    { name: "Acme Corp", id: "CLT-001" },
                    { name: "Globex Industries", id: "CLT-002" },
                    { name: "Wayne Technologies", id: "CLT-004" },
                  ].map((client, idx) => (
                    <div
                      key={idx}
                      className="flex items-center justify-between"
                    >
                      <div className="flex items-center">
                        <div className="h-6 w-6 rounded-full bg-purple-900/30 text-purple-500 text-xs flex items-center justify-center font-medium mr-2">
                          {client.name.substring(0, 2)}
                        </div>
                        <div>
                          <div className="font-medium text-sm text-white dark:text-white">
                            {client.name}
                          </div>
                          <div className="text-xs text-neutral-400 dark:text-neutral-400">
                            {client.id}
                          </div>
                        </div>
                      </div>
                      <Link href={`/dashboard/inventory?clientId=${client.id}`}>
                        <Button
                          variant="ghost"
                          size="sm"
                          className="h-7 px-2 text-xs text-gray-300 hover:text-purple-400 dark:text-gray-300 dark:hover:text-purple-300"
                        >
                          Manage
                        </Button>
                      </Link>
                    </div>
                  ))}
                </div>
              </div>

              <div className="lg:col-span-8 border border-neutral-800 bg-neutral-900 rounded-md p-3">
                <div className="flex items-center justify-between mb-3">
                  <h4 className="text-sm font-mono text-white dark:text-white">RECENT ACTIVITY</h4>
                  <span className="text-xs text-neutral-500 font-mono dark:text-neutral-400">
                    LAST UPDATED: 10:21:02
                  </span>
                </div>

                <div className="space-y-3">
                  {[
                    {
                      event: "New API key generated",
                      client: "Acme Corp",
                      time: "5m ago",
                      type: "security",
                      clientId: "CLT-001",
                    },
                    {
                      event: "Rate limit increased",
                      client: "Globex Industries",
                      time: "32m ago",
                      type: "config",
                      clientId: "CLT-002",
                    },
                    {
                      event: "New client onboarded",
                      client: "Initech",
                      time: "2h ago",
                      type: "system",
                      clientId: "CLT-005",
                    },
                  ].map((activity, idx) => (
                    <div key={idx} className="flex items-start">
                      <div
                        className={`h-2 w-2 rounded-full mt-2 ${
                          activity.type === "security"
                            ? "bg-red-500"
                            : activity.type === "config"
                            ? "bg-blue-500"
                            : "bg-green-500"
                        }`}
                      />
                      <div className="ml-3">
                        <div className="text-sm text-white dark:text-white">{activity.event}</div>
                        <div className="text-xs text-neutral-500 dark:text-neutral-400 flex items-center mt-0.5">
                          <span>{activity.client}</span>
                          <span className="mx-1.5">•</span>
                          <span>{activity.time}</span>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

// Apply the dashboard layout to this page
ClientManagementPage.layout = page => <DashboardLayout>{page}</DashboardLayout>;

export default ClientManagementPage;