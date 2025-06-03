import * as React from 'react';
import { Shield, User } from 'lucide-react';
import { Tabs, TabsList, TabsTrigger } from '../ui/Tabs';
import UserCard from './UserCard';

function UserList({
  users,
  onUserClick,
  activeTab,
  setActiveTab,
  currentPage = 1,
  usersPerPage = 10,
}) {
  // Filter users based on active tab
  const filteredUsersByTab = users.filter((user) => {
    if (activeTab === 'system-users') {
      return user.is_admin || user.is_super_admin;
    } else {
      return !user.is_admin && !user.is_super_admin;
    }
  });

  // Apply pagination
  const indexOfLastUser = currentPage * usersPerPage;
  const indexOfFirstUser = indexOfLastUser - usersPerPage;
  const currentUsers = filteredUsersByTab.slice(
    indexOfFirstUser,
    indexOfLastUser,
  );

  const systemUsers = currentUsers.filter(
    (user) => user.is_admin || user.is_super_admin,
  );
  const clientUsers = currentUsers.filter(
    (user) => !user.is_admin && !user.is_super_admin,
  );

  return (
    <div className="bg-black border border-blue-500/30 rounded-lg overflow-hidden">
      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList>
          <TabsTrigger value="system-users">
            <Shield className="h-4 w-4 mr-2" />
            SYSTEM USERS
          </TabsTrigger>
          <TabsTrigger value="client-users">
            <User className="h-4 w-4 mr-2" />
            CLIENT USERS
          </TabsTrigger>
        </TabsList>
      </Tabs>
      <div className="p-4 bg-black/60 animate-fade-in">
        {activeTab === 'system-users' ? (
          <>
            <h3 className="text-xs font-semibold text-blue-400 uppercase tracking-wider font-mono mb-3">
              SYSTEM ADMINISTRATORS
            </h3>
            {systemUsers.length > 0 ? (
              systemUsers.map((user) => (
                <UserCard key={user.id} user={user} onClick={onUserClick} />
              ))
            ) : (
              <div className="text-sm text-blue-300/70 p-3 bg-black/40 border border-blue-500/20 rounded-lg font-mono">
                No system administrators found.
              </div>
            )}
          </>
        ) : (
          <>
            <h3 className="text-xs font-semibold text-blue-400 uppercase tracking-wider font-mono mb-3">
              CLIENT USERS
            </h3>
            {clientUsers.length > 0 ? (
              clientUsers.map((user) => (
                <UserCard key={user.id} user={user} onClick={onUserClick} />
              ))
            ) : (
              <div className="text-sm text-blue-300/70 p-3 bg-black/40 border border-blue-500/20 rounded-lg font-mono">
                No client users found.
              </div>
            )}
          </>
        )}
      </div>
    </div>
  );
}

export default UserList;
