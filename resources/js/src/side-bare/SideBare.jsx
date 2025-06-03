import * as React from 'react';
import { Link, usePage } from '@inertiajs/react';
import {
  HomeIcon,
  KeyIcon,
  UsersIcon,
  UserGroupIcon,
  ArchiveBoxIcon,
  ChartBarIcon,
  DocumentTextIcon,
  SunIcon,
  MoonIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  Cog6ToothIcon,
  ArrowRightOnRectangleIcon,
} from '@heroicons/react/24/outline';

// Reusable Navigation Item Component
const NavItem = ({
  href,
  icon: Icon,
  name,
  description,
  isActive,
  collapsed,
}) => (
  <Link
    href={href}
    className={`flex items-center ${
      collapsed ? 'justify-center' : 'px-4'
    } py-3 rounded-xl transition-all duration-300 ${
      isActive
        ? 'bg-gradient-to-r from-indigo-600/20 to-indigo-800/20 text-indigo-200 font-semibold'
        : 'text-gray-300 hover:bg-gray-800/50 hover:text-indigo-300 dark:hover:bg-gray-700/50 dark:hover:text-indigo-400'
    } group relative`}
    title={description}
  >
    <Icon
      className={`h-6 w-6 ${collapsed ? 'mx-auto' : 'mr-4'} ${
        isActive
          ? 'text-indigo-300 dark:text-indigo-400'
          : 'text-gray-400 group-hover:text-indigo-300 dark:group-hover:text-indigo-400'
      } transition-colors`}
    />
    {!collapsed && (
      <>
        <span className="text-sm">{name}</span>
        <span className="absolute left-0 top-0 h-full w-1 bg-indigo-400 opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
      </>
    )}
  </Link>
);

// Reusable Navigation Button Component (for actions like logout)
const NavItemButton = ({
  icon: Icon,
  name,
  description,
  onClick,
  collapsed,
}) => (
  <button
    onClick={onClick}
    className={`flex w-full items-center ${
      collapsed ? 'justify-center' : 'px-3'
    } py-2 rounded-lg text-gray-300 hover:bg-gray-800/50 hover:text-indigo-300 dark:hover:bg-gray-700/50 dark:hover:text-indigo-400 transition-all duration-300 group relative`}
    title={description}
  >
    <Icon
      className={`h-5 w-5 ${
        collapsed ? 'mx-auto' : 'mr-3'
      } text-gray-400 group-hover:text-indigo-300 dark:group-hover:text-indigo-400`}
    />
    {!collapsed && (
      <>
        <span className="text-sm">{name}</span>
        <span className="absolute left-0 top-0 h-full w-1 bg-indigo-400 opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
      </>
    )}
  </button>
);

// Reusable Theme Toggle Button Component
const ThemeToggle = ({ theme, setTheme }) => (
  <button
    onClick={() => setTheme(theme === 'dark' ? 'light' : 'dark')}
    className="p-2 rounded-full bg-gray-800/50 hover:bg-gray-700/50 dark:hover:bg-gray-600/50 text-gray-300 hover:text-indigo-300 dark:hover:text-indigo-400 transition-all duration-300"
    aria-label="Toggle theme"
  >
    {theme === 'dark' ? (
      <SunIcon className="h-5 w-5" />
    ) : (
      <MoonIcon className="h-5 w-5" />
    )}
  </button>
);

// Reusable Logo Component
const Logo = ({ collapsed }) => (
  <Link href="/dashboard" className="flex items-center group">
    <DocumentTextIcon className="h-5 w-5 text-indigo-400 group-hover:text-indigo-300 dark:group-hover:text-indigo-600 transition-colors" />
    {!collapsed && (
      <span className="ml-3 text-base font-semibold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-indigo-200 to-indigo-400 group-hover:from-indigo-300 group-hover:to-indigo-500 dark:from-indigo-200 dark:to-indigo-400">
        Dashboard
      </span>
    )}
  </Link>
);

// Navigation Items Configuration
const navigationItems = [
  {
    name: 'Dashboard',
    href: '/admin/dashboard',
    icon: HomeIcon,
    description: 'Main dashboard overview',
  },
  {
    name: 'API',
    href: '/admin/dashboard/api-management',
    icon: KeyIcon,
    description: 'Manage API keys and settings',
  },
  {
    name: 'Clients',
    href: '/admin/dashboard/client-management',
    icon: UsersIcon,
    description: 'Client management',
  },
  {
    name: 'Users',
    href: '/admin/dashboard/users',
    icon: UserGroupIcon,
    description: 'User management',
  },
  {
    name: 'Inventory',
    href: '/admin/dashboard/inventory',
    icon: ArchiveBoxIcon,
    description: 'Inventory management',
  },
  {
    name: 'Analytics',
    href: '/admin/dashboard/analytics',
    icon: ChartBarIcon,
    description: 'Usage analytics and metrics',
  },
];

function Sidebar() {
  const { url } = usePage();
  const [collapsed, setCollapsed] = React.useState(false);
  const [theme, setTheme] = React.useState('dark');

  // Apply theme to document
  React.useEffect(() => {
    document.documentElement.classList.toggle('dark', theme === 'dark');
  }, [theme]);

  return (
    <aside
      className={`h-screen transition-all duration-300 ease-in-out shrink-0 ${
        collapsed ? 'w-16' : 'w-64'
      } bg-gradient-to-b from-gray-900 to-gray-950 dark:from-gray-900 dark:to-gray-950 border-r border-gray-800/30 dark:border-gray-700 shadow-xl`}
    >
      <div className="flex flex-col h-full text-gray-100 dark:text-gray-100">
        {/* Logo and theme toggle */}
        <div className="p-4 flex items-center justify-between bg-gray-900/50 border-b border-gray-800/50 dark:bg-gray-900/50 dark:border-gray-700/50">
          <Logo collapsed={collapsed} />
          {!collapsed && <ThemeToggle theme={theme} setTheme={setTheme} />}
        </div>

        {/* Navigation items */}
        <div className="flex-1 overflow-y-auto px-4 py-6">
          <nav className="flex flex-col gap-2">
            {navigationItems.map((item) => (
              <NavItem
                key={item.name}
                href={item.href}
                icon={item.icon}
                name={item.name}
                description={item.description}
                isActive={url === item.href}
                collapsed={collapsed}
              />
            ))}
          </nav>
        </div>

        {/* Collapse button */}
        <div className="px-3 py-2 border-t border-gray-800/50 dark:border-gray-700/50">
          <button
            onClick={() => setCollapsed(!collapsed)}
            className="w-full flex justify-center items-center p-2 rounded-full bg-gray-800/50 hover:bg-gray-700/50 dark:hover:bg-gray-600/50 text-gray-300 hover:text-indigo-300 dark:hover:text-indigo-400 transition-all duration-300"
            aria-label={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
          >
            {collapsed ? (
              <ChevronRightIcon className="h-5 w-5" />
            ) : (
              <ChevronLeftIcon className="h-5 w-5" />
            )}
          </button>
        </div>
        {/* Settings and logout */}
        <div className="p-3 border-t border-gray-800/50 dark:border-gray-700/50">
          <NavItem
            href="/dashboard/settings"
            icon={Cog6ToothIcon}
            name="Settings"
            description="Application settings"
            isActive={url === '/dashboard/settings'}
            collapsed={collapsed}
          />
          <NavItemButton
            icon={ArrowRightOnRectangleIcon}
            name="Logout"
            description="Log out of your account"
            collapsed={collapsed}
            onClick={() => {
              // Replace with Inertia.post('/logout') if needed
              console.log('Logout clicked');
            }}
          />
        </div>
      </div>
    </aside>
  );
}

export default Sidebar;
