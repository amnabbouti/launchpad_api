import * as React from 'react';
import { Clock } from 'lucide-react';

function UserCard({ user, onClick }) {
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
    <div
      className="flex items-center justify-between p-3 bg-black/40 border border-blue-500/20 rounded-lg hover:bg-blue-500/10 hover:border-blue-500/40 transition-colors cursor-pointer"
      onClick={() => onClick(user)}
    >
      <div className="flex items-center space-x-3">
        <div className="h-10 w-10 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center font-mono border border-blue-500/30">
          {getUserInitials(user)}
        </div>
        <div>
          <div className="text-sm font-medium text-blue-100">
            {getUserName(user)}
          </div>
          <div className="text-xs text-blue-300/70 font-mono">{user.email}</div>
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
              {user.is_super_admin
                ? 'SUPER ADMIN'
                : user.is_admin
                ? 'ADMIN'
                : 'CLIENT'}
            </span>
            <span
              className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-mono ${
                user.is_active
                  ? 'bg-green-500/20 text-green-400 border border-green-500/30'
                  : 'bg-red-500/20 text-red-400 border border-red-500/30'
              }`}
            >
              {user.is_active ? 'ACTIVE' : 'INACTIVE'}
            </span>
          </div>
        </div>
      </div>
      <div className="flex items-center text-xs text-blue-300/50 font-mono">
        <Clock className="h-3 w-3 mr-1" />
        {getRelativeTime(user.last_active)}
      </div>
    </div>
  );
}

export default UserCard;
