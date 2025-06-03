import * as React from 'react';
import { ChevronLeft, Power, Trash2, Edit } from 'lucide-react';

function UserPanel({ user, onClose, onEdit, onToggleStatus, onDelete }) {
  const getUserName = (user) => {
    if (user.first_name || user.last_name) {
      return `${user.first_name || ''} ${user.last_name || ''}`.trim();
    }
    return user.email.split('@')[0];
  };

  const getUserInitials = (user) => {
    if (user.first_name && user.last_name) {
      return `${user.first_name.charAt(0)}${user.last_name.charAt(
        0,
      )}`.toUpperCase();
    } else if (user.first_name) {
      return user.first_name.substring(0, 2).toUpperCase();
    } else if (user.last_name) {
      return user.last_name.substring(0, 2).toUpperCase();
    }
    return user.email.substring(0, 2).toUpperCase();
  };

  const getUserRole = (user) => {
    if (user.is_super_admin) return 'Super Admin';
    if (user.is_admin) return 'Admin';
    return 'User';
  };

  const getRelativeTime = (timestamp) => {
    if (!timestamp) return 'Never';
    const now = new Date();
    const loginTime = new Date(timestamp);
    const diffMs = now.getTime() - loginTime.getTime();
    const diffMinutes = Math.round(diffMs / (60 * 1000));
    if (diffMinutes < 1) return 'Just now';
    if (diffMinutes < 60) return `${diffMinutes} min ago`;
    const diffHours = Math.round(diffMinutes / 60);
    if (diffHours < 24) return `${diffHours} hr ago`;
    const diffDays = Math.round(diffHours / 24);
    if (diffDays < 30) return `${diffDays} days ago`;
    const diffMonths = Math.round(diffDays / 30);
    return `${diffMonths} mo ago`;
  };

  return (
    <div className="absolute inset-0 bg-black border border-blue-500/30 rounded-lg overflow-hidden flex flex-col z-10 animate-slide-in-right">
      <div className="bg-black/80 border-b border-blue-500/30 px-3 py-2 flex items-center justify-between">
        <div className="flex items-center space-x-2">
          <button
            className="text-blue-400 hover:text-blue-300 p-1 rounded-full hover:bg-blue-500/20 transition-colors"
            onClick={onClose}
            aria-label="Back to user list"
          >
            <ChevronLeft className="h-4 w-4" />
          </button>
          <h3 className="text-sm font-mono text-blue-300">
            MANAGE {user.is_admin || user.is_super_admin ? 'ADMIN' : 'USER'}{' '}
            ACCOUNT
          </h3>
        </div>
        <div
          className={`px-2 py-0.5 rounded-sm text-xs font-mono ${
            user.is_active
              ? 'bg-green-500/20 text-green-400'
              : 'bg-red-500/20 text-red-400'
          }`}
        >
          {user.is_active ? 'ACTIVE' : 'INACTIVE'}
        </div>
      </div>
      <div className="p-4 overflow-y-auto flex-grow bg-black/60">
        <div className="flex items-center space-x-3 mb-4">
          <div className="h-12 w-12 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center font-mono text-lg border border-blue-500/30">
            {getUserInitials(user)}
          </div>
          <div>
            <h3 className="text-base font-medium text-blue-100">
              {getUserName(user)}
            </h3>
            <div className="text-xs text-blue-300/70 font-mono">
              {user.email}
            </div>
            <div className="flex items-center mt-1 space-x-2">
              <span
                className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-mono ${
                  user.is_super_admin
                    ? 'bg-red-500/20 text-red-400 border border-red-500/30'
                    : user.is_admin
                    ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30'
                    : 'bg-blue-500/20 text-blue-400 border border-blue-500/30'
                }`}
              >
                {getUserRole(user)}
              </span>
              <span className="text-xs text-blue-300/50 font-mono">
                ID: {user.id}
              </span>
            </div>
          </div>
        </div>
        <div className="flex space-x-4 mb-4">
          <button
            className={`flex items-center space-x-2 px-3 py-2 rounded-md border font-mono text-xs ${
              user.is_active
                ? 'border-red-500/50 hover:border-red-400 text-red-400 hover:bg-red-500/10'
                : 'border-green-500/50 hover:border-green-400 text-green-400 hover:bg-green-500/10'
            } transition-colors`}
            onClick={onToggleStatus}
          >
            <Power className="h-4 w-4" />
            <span>{user.is_active ? 'DEACTIVATE' : 'ACTIVATE'}</span>
          </button>{' '}
          <button
            className="border border-blue-500/50 hover:border-blue-400 text-blue-400 hover:bg-blue-500/10 flex items-center space-x-2 px-3 py-2 rounded-md font-mono text-xs transition-colors"
            onClick={onEdit}
          >
            <Edit className="h-4 w-4" />
            <span>EDIT INFO</span>
          </button>
        </div>
        {(user.is_admin || user.is_super_admin) && (
          <div className="bg-black/40 border border-blue-500/20 rounded-md p-3 mb-4">
            <h4 className="text-sm font-mono mb-2 text-blue-300">
              ADMIN PERMISSIONS
            </h4>
            <div className="space-y-2">
              {[
                { name: 'User Management', enabled: true },
                { name: 'System Configuration', enabled: user.is_super_admin },
                { name: 'API Access Control', enabled: true },
                { name: 'Audit Logs', enabled: user.is_super_admin },
              ].map((permission) => (
                <div
                  key={permission.name}
                  className="flex items-center justify-between"
                >
                  <span className="text-sm text-blue-200">
                    {permission.name}
                  </span>
                  <span
                    className={`text-sm font-mono ${
                      permission.enabled ? 'text-green-400' : 'text-red-400'
                    }`}
                  >
                    {permission.enabled ? 'ENABLED' : 'DISABLED'}
                  </span>
                </div>
              ))}
            </div>
          </div>
        )}
        <div className="bg-black/40 border border-blue-500/20 rounded-md p-3 mb-4">
          <h4 className="text-sm font-mono mb-2 text-blue-300">
            ACCOUNT INFORMATION
          </h4>
          <div className="space-y-2">
            <div className="flex justify-between">
              <span className="text-sm text-blue-300/70 font-mono">
                Created
              </span>
              <span className="text-sm text-blue-200 font-mono">
                {user.created_at || 'Unknown'}
              </span>
            </div>
            <div className="flex justify-between">
              <span className="text-sm text-blue-300/70 font-mono">
                Last Active
              </span>
              <span className="text-sm text-blue-200 font-mono">
                {getRelativeTime(user.last_active)}
              </span>
            </div>
          </div>
        </div>{' '}
        <button
          className="w-full border border-red-500/50 hover:border-red-400 text-red-400 hover:bg-red-500/10 flex items-center justify-center space-x-2 px-3 py-2 rounded-md font-mono text-xs transition-colors"
          onClick={onDelete}
        >
          <Trash2 className="h-4 w-4" />
          <span>DELETE ACCOUNT</span>
        </button>
      </div>
    </div>
  );
}

export default UserPanel;
