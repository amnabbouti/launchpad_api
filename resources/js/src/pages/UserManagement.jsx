import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import DashboardLayout from '../layouts/DashboardLayout';
import { router } from '@inertiajs/react';

// Import all user management components
import UserStats from '../Users/UserState';
import UserFilters from '../Users/UserFilters';
import UserList from '../Users/UserList';
import UserPanel from '../Users/UserPanel';
import UserPagination from '../Users/UserPagination';
import EditUserModal from '../Users/EditUserModal';

function UserManagement({ users = [], roles = [], organizations = [] }) {
  // State management
  const [activeTab, setActiveTab] = useState('system-users');
  const [selectedUser, setSelectedUser] = useState(null);
  const [isManagingUser, setIsManagingUser] = useState(false);
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);

  // Debug: Log state changes
  console.log('isManagingUser:', isManagingUser);
  console.log('selectedUser:', selectedUser);
  const [searchQuery, setSearchQuery] = useState('');
  const [activeFilter, setActiveFilter] = useState('all');
  const [currentPage, setCurrentPage] = useState(1);
  const usersPerPage = 10;
  // Reset selected user when users change
  useEffect(() => {
    setSelectedUser(null);
    setIsManagingUser(false);
  }, [users]);

  // Reset current page when tab changes
  useEffect(() => {
    setCurrentPage(1);
  }, [activeTab]);
  // Helper functions
  const getUserName = (user) => {
    if (user.first_name || user.last_name) {
      return `${user.first_name || ''} ${user.last_name || ''}`.trim();
    }
    return user.email.split('@')[0];
  }; // Calculate stats
  const stats = {
    totalUsers: users.length,
    activeUsers: users.filter((user) => user.is_active).length,
    admins: users.filter((user) => user.is_admin || user.is_super_admin).length,
    newToday: users.filter((user) => {
      const today = new Date().toDateString();
      const userCreated = new Date(user.created_at).toDateString();
      return today === userCreated;
    }).length,
  };

  // Filter users based on search and filter criteria
  const filteredUsers = users
    .filter((user) => {
      const searchTerm = searchQuery.toLowerCase();
      const userName = getUserName(user).toLowerCase();
      const email = user.email.toLowerCase();
      return userName.includes(searchTerm) || email.includes(searchTerm);
    })
    .filter((user) => {
      if (activeFilter === 'all') return true;
      if (activeFilter === 'active') return user.is_active;
      if (activeFilter === 'inactive') return !user.is_active;
      if (activeFilter === 'admins')
        return user.is_admin || user.is_super_admin;
      if (activeFilter === 'clients')
        return !user.is_admin && !user.is_super_admin;
      return true;
    });
  // Calculate pagination based on active tab
  const tabFilteredUsers = filteredUsers.filter((user) => {
    if (activeTab === 'system-users') {
      return user.is_admin || user.is_super_admin;
    } else {
      return !user.is_admin && !user.is_super_admin;
    }
  });

  // Pagination
  const indexOfLastUser = currentPage * usersPerPage;
  const indexOfFirstUser = indexOfLastUser - usersPerPage;
  const currentUsers = tabFilteredUsers.slice(
    indexOfFirstUser,
    indexOfLastUser,
  );
  const totalPages = Math.ceil(tabFilteredUsers.length / usersPerPage); // Event handlers
  const handleUserClick = (user) => {
    setSelectedUser(user);
  };
  const handleEditUser = () => {
    setIsEditModalOpen(true);
  };

  const handleCloseEditModal = () => {
    setIsEditModalOpen(false);
  }; // Server action to update user
  const updateUser = async (userId, data) => {
    // Call server action directly - most Filament-like approach
    await router.post(
      '/admin/users/update',
      {
        user_id: userId,
        ...data,
      },
      {
        preserveState: true,
        only: ['users'], // Only refresh users data
        onSuccess: () => {
          setIsEditModalOpen(false);
          console.log('User updated successfully in database');
        },
        onError: (errors) => {
          console.error('User update failed:', errors);
        },
      },
    );
  };

  const handleUserSaved = async (userId, updatedData) => {
    // Use the server action to update user
    await updateUser(userId, updatedData);
  };

  const handleCloseUserPanel = () => {
    setSelectedUser(null);
  };

  return (
    <div className="p-6 bg-black text-white min-h-screen">
      {/* Header */}
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-blue-300 mb-2">
          User Management
        </h1>
        <p className="text-sm text-blue-400/70 font-mono">
          Manage system administrators and client users
        </p>
      </div>
      {/* Stats Cards */}
      <div className="mb-6">
        <UserStats stats={stats} />
      </div>
      {/* Search and Filter Controls */}
      <div className="mb-6">
        <UserFilters
          searchQuery={searchQuery}
          setSearchQuery={setSearchQuery}
          activeFilter={activeFilter}
          setActiveFilter={setActiveFilter}
        />
      </div>{' '}
      {/* Main Content Area */}
      <div className="relative">
        {/* User List - Full Width */}
        <UserList
          users={filteredUsers}
          onUserClick={handleUserClick}
          activeTab={activeTab}
          setActiveTab={setActiveTab}
          currentPage={currentPage}
          usersPerPage={usersPerPage}
        />

        {/* Pagination */}
        {totalPages > 1 && (
          <div className="mt-4">
            <UserPagination
              currentPage={currentPage}
              totalPages={totalPages}
              onPageChange={setCurrentPage}
              totalUsers={tabFilteredUsers.length}
              usersPerPage={usersPerPage}
              indexOfFirstUser={indexOfFirstUser}
              indexOfLastUser={Math.min(
                indexOfLastUser,
                tabFilteredUsers.length,
              )}
            />
          </div>
        )}

        {/* User Panel - Overlay */}
        {selectedUser && (
          <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div className="bg-black border border-blue-500/30 rounded-lg max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto">
              <UserPanel
                user={selectedUser}
                onClose={handleCloseUserPanel}
                onEdit={handleEditUser}
              />
            </div>
          </div>
        )}
      </div>{' '}
      {/* Edit User Modal */}
      <EditUserModal
        user={selectedUser}
        isOpen={isEditModalOpen}
        onClose={handleCloseEditModal}
        onSave={handleUserSaved}
        roles={roles}
        organizations={organizations}
      />
    </div>
  );
}

// Apply dashboard layout
UserManagement.layout = (page) => <DashboardLayout>{page}</DashboardLayout>;

export default UserManagement;
