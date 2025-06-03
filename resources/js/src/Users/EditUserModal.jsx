import * as React from 'react';
import { X, Save, User } from 'lucide-react';
import { Button } from '../ui/Button';
import { Input } from '../ui/Input';
import { Label } from '../ui/Label';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '../ui/Dialog';

function EditUserModal({
  user,
  isOpen,
  onClose,
  onSave,
  roles = [],
  organizations = [],
}) {
  const [formData, setFormData] = React.useState({
    first_name: '',
    last_name: '',
    email: '',
    org_role: '',
    phone_number: '',
    role_id: '',
    org_id: '',
  });
  const [saving, setSaving] = React.useState(false);

  // Update form data when user changes
  React.useEffect(() => {
    if (user && isOpen) {
      setFormData({
        first_name: user.first_name || '',
        last_name: user.last_name || '',
        email: user.email || '',
        org_role: user.org_role || '',
        phone_number: user.phone_number || '',
        role_id: user.role_id || '',
        org_id: user.org_id || '',
      });
    }
  }, [user, isOpen]);
  const handleSave = async (e) => {
    e.preventDefault();
    if (!user) return;

    setSaving(true);

    // Call the onSave prop with the updated data
    // The parent component will handle the actual saving
    if (onSave) {
      await onSave(user.id, formData);
    }

    setSaving(false);
    onClose();
  };

  const handleClose = () => {
    onClose();
  };

  if (!user) return null;

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="sm:max-w-md bg-black border-blue-500/30 text-white">
        <DialogHeader>
          <DialogTitle className="text-blue-300 flex items-center gap-2 font-mono">
            <User className="h-5 w-5" />
            EDIT ASTRONAUT PROFILE
          </DialogTitle>
        </DialogHeader>

        <form onSubmit={handleSave} className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div>
              <Label
                htmlFor="first_name"
                className="text-blue-400 font-mono text-sm"
              >
                FIRST NAME
              </Label>{' '}
              <Input
                id="first_name"
                value={formData.first_name}
                onChange={(e) =>
                  setFormData((prev) => ({
                    ...prev,
                    first_name: e.target.value,
                  }))
                }
                className="bg-black/70 border-blue-500/50 text-blue-100 placeholder-blue-300/50 font-mono focus:border-blue-400 focus:ring-blue-400"
                placeholder="Enter first name..."
              />
            </div>
            <div>
              <Label
                htmlFor="last_name"
                className="text-blue-400 font-mono text-sm"
              >
                LAST NAME
              </Label>{' '}
              <Input
                id="last_name"
                value={formData.last_name}
                onChange={(e) =>
                  setFormData((prev) => ({
                    ...prev,
                    last_name: e.target.value,
                  }))
                }
                className="bg-black/70 border-blue-500/50 text-blue-100 placeholder-blue-300/50 font-mono focus:border-blue-400 focus:ring-blue-400"
                placeholder="Enter last name..."
              />
            </div>
          </div>
          <div>
            <Label htmlFor="email" className="text-blue-400 font-mono text-sm">
              EMAIL ADDRESS
            </Label>{' '}
            <Input
              id="email"
              type="email"
              value={formData.email}
              onChange={(e) =>
                setFormData((prev) => ({ ...prev, email: e.target.value }))
              }
              className="bg-black/70 border-blue-500/50 text-blue-100 placeholder-blue-300/50 font-mono focus:border-blue-400 focus:ring-blue-400"
              placeholder="Enter email address..."
            />{' '}
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <Label
                htmlFor="org_role"
                className="text-blue-400 font-mono text-sm"
              >
                ORGANIZATION ROLE
              </Label>
              <Input
                id="org_role"
                value={formData.org_role}
                onChange={(e) =>
                  setFormData((prev) => ({ ...prev, org_role: e.target.value }))
                }
                className="bg-black/70 border-blue-500/50 text-blue-100 placeholder-blue-300/50 font-mono focus:border-blue-400 focus:ring-blue-400"
                placeholder="e.g. Senior Developer, Manager..."
              />
            </div>
            <div>
              <Label
                htmlFor="phone_number"
                className="text-blue-400 font-mono text-sm"
              >
                PHONE NUMBER
              </Label>
              <Input
                id="phone_number"
                value={formData.phone_number}
                onChange={(e) =>
                  setFormData((prev) => ({
                    ...prev,
                    phone_number: e.target.value,
                  }))
                }
                className="bg-black/70 border-blue-500/50 text-blue-100 placeholder-blue-300/50 font-mono focus:border-blue-400 focus:ring-blue-400"
                placeholder="Enter phone number..."
              />
            </div>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <Label
                htmlFor="role_id"
                className="text-blue-400 font-mono text-sm"
              >
                SYSTEM ROLE
              </Label>
              <select
                id="role_id"
                value={formData.role_id}
                onChange={(e) =>
                  setFormData((prev) => ({ ...prev, role_id: e.target.value }))
                }
                className="w-full bg-black/70 border border-blue-500/50 text-blue-100 font-mono focus:border-blue-400 focus:ring-blue-400 rounded-md px-3 py-2"
              >
                <option value="">Select Role...</option>
                {roles.map((role) => (
                  <option key={role.id} value={role.id}>
                    {role.title}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <Label
                htmlFor="org_id"
                className="text-blue-400 font-mono text-sm"
              >
                ORGANIZATION
              </Label>
              <select
                id="org_id"
                value={formData.org_id}
                onChange={(e) =>
                  setFormData((prev) => ({ ...prev, org_id: e.target.value }))
                }
                className="w-full bg-black/70 border border-blue-500/50 text-blue-100 font-mono focus:border-blue-400 focus:ring-blue-400 rounded-md px-3 py-2"
              >
                <option value="">Select Organization...</option>
                {organizations.map((org) => (
                  <option key={org.id} value={org.id}>
                    {org.name}
                  </option>
                ))}
              </select>
            </div>
          </div>{' '}
          <div className="flex justify-end space-x-2 pt-4 border-t border-blue-500/30">
            <Button
              variant="outline"
              onClick={handleClose}
              className="border-blue-500/50 text-blue-400 hover:bg-blue-500/10 font-mono"
            >
              CANCEL
            </Button>{' '}
            <Button
              type="submit"
              disabled={saving}
              className="bg-blue-600 hover:bg-blue-700 text-white border border-blue-500/50 font-mono"
            >
              <Save className="h-4 w-4 mr-2" />
              {saving ? 'SAVING...' : 'SAVE CHANGES'}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export default EditUserModal;
