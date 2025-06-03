import * as React from "react";
import { Plus, Pen, Trash, Loader2 } from "lucide-react";
import { Button } from "../ui/Button";
import { Input } from "../ui/Input";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger, DialogDescription } from "../ui/Dialog";

function CategoryManagement({ categories: initialCategories = [] }) {
  const [categories, setCategories] = React.useState(initialCategories);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState(null);
  const [newCategoryName, setNewCategoryName] = React.useState("");
  const [editingCategory, setEditingCategory] = React.useState(null);
  const [isAdding, setIsAdding] = React.useState(false);

  const handleAddCategory = () => {
    if (!newCategoryName.trim()) {
      return;
    }
    const newCategory = {
      id: categories.length > 0 ? Math.max(...categories.map((c) => c.id)) + 1 : 1,
      name: newCategoryName.trim(),
      organization_id: 1,
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString(),
    };
    setCategories([...categories, newCategory]);
    setNewCategoryName("");
    setIsAdding(false);
  };

  const handleOpenEditDialog = (category) => {
    setEditingCategory(category);
  };

  const handleSaveEdit = () => {
    if (!editingCategory || !editingCategory.name.trim()) {
      return;
    }
    setCategories(
      categories.map((c) =>
        c.id === editingCategory.id
          ? {
              ...c,
              name: editingCategory.name.trim(),
              updated_at: new Date().toISOString(),
            }
          : c
      )
    );
    setEditingCategory(null);
  };

  const handleDeleteCategory = (id) => {
    setCategories(categories.filter((category) => category.id !== id));
  };

  React.useEffect(() => {
    const loadCategories = async () => {
      setLoading(true);
      setError(null);

      try {
        // Mock data for demo purposes
        const mockCategories = [
          {
            id: 1,
            name: "Electronics",
            organization_id: 1,
            created_at: "2023-01-01T00:00:00.000Z",
            updated_at: "2023-01-01T00:00:00.000Z",
          },
          {
            id: 2,
            name: "Office Supplies",
            organization_id: 1,
            created_at: "2023-01-01T00:00:00.000Z",
            updated_at: "2023-01-01T00:00:00.000Z",
          },
        ];

        // Simulate API delay
        setTimeout(() => {
          setCategories(mockCategories);
          setLoading(false);
        }, 500);
      } catch (err) {
        console.error("Failed to load categories:", err);
        setError(err instanceof Error ? err.message : "Failed to load categories");
        setLoading(false);
      }
    };

    loadCategories();
  }, []);

  return (
    <>
      <Dialog open={isAdding} onOpenChange={setIsAdding}>
        <DialogContent className="bg-neutral-900 border-neutral-700 text-neutral-100 max-w-md">
          <DialogHeader>
            <DialogTitle>Add New Category</DialogTitle>
            <DialogDescription id="add-category-description" className="sr-only">
              Use this form to add a new category to the system.
            </DialogDescription>
          </DialogHeader>
          <div className="mt-4 space-y-3">
            <div>
              <Input
                placeholder="Category Name"
                value={newCategoryName}
                onChange={(e) => setNewCategoryName(e.target.value)}
                className="w-full bg-neutral-800 border-neutral-700 text-neutral-100 placeholder-neutral-400"
              />
            </div>
            <div className="flex justify-end gap-2 mt-4">
              <Button
                variant="outline"
                size="sm"
                className="border-neutral-700 hover:bg-neutral-700 text-white dark:border-neutral-700 dark:hover:bg-neutral-700 dark:text-white"
                onClick={() => {
                  setIsAdding(false);
                  setNewCategoryName("");
                }}
              >
                Cancel
              </Button>
              <Button
                size="sm"
                className="bg-indigo-600 hover:bg-indigo-700 text-white dark:bg-indigo-600 dark:hover:bg-indigo-700"
                onClick={handleAddCategory}
              >
                Add Category
              </Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>

      <div className="overflow-x-auto rounded-lg border border-neutral-800 bg-neutral-900">
        <table className="w-full">
          <thead>
            <tr className="text-xs font-mono text-neutral-400 bg-neutral-800/60">
              <th className="px-4 py-2 text-left">NAME</th>
              <th className="px-4 py-2 text-right">ACTIONS</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-neutral-800">
            {loading && (
              <tr>
                <td colSpan={2} className="text-center text-neutral-400 dark:text-neutral-400 py-4">
                  <div className="flex justify-center items-center">
                    <Loader2 className="h-5 w-5 animate-spin mr-2" />
                    Loading categories...
                  </div>
                </td>
              </tr>
            )}
            {!loading && error && (
              <tr>
                <td colSpan={2} className="text-center text-red-400 dark:text-red-400 py-4">
                  Error: {error}
                </td>
              </tr>
            )}
            {!loading && !error && categories.length === 0 && (
              <tr>
                <td colSpan={2} className="text-center text-neutral-400 dark:text-neutral-400 py-4">
                  No categories found.
                </td>
              </tr>
            )}
            {categories.map((category) => (
              <tr key={category.id} className="text-sm hover:bg-neutral-800/40 dark:hover:bg-neutral-800/40">
                <td className="px-4 py-3 font-medium text-white dark:text-white">{category.name}</td>
                <td className="px-4 py-3 text-right">
                  <div className="flex justify-end space-x-1">
                    <Dialog open={editingCategory?.id === category.id} onOpenChange={(isOpen) => !isOpen && setEditingCategory(null)}>
                      <DialogTrigger asChild>
                        <Button
                          variant="ghost"
                          size="sm"
                          className="h-7 w-7 p-0 text-neutral-400 hover:text-white dark:text-neutral-400 dark:hover:text-white"
                          onClick={() => handleOpenEditDialog(category)}
                          aria-label={`Edit ${category.name}`}
                        >
                          <Pen className="h-3.5 w-3.5" />
                        </Button>
                      </DialogTrigger>
                      <DialogContent className="bg-neutral-900 border-neutral-700 text-neutral-100 max-w-md">
                        <DialogHeader>
                          <DialogTitle>Edit Category</DialogTitle>
                          <DialogDescription
                            id={`edit-category-description-${category.id}`}
                            className="sr-only"
                          >
                            Use this form to edit the category name.
                          </DialogDescription>
                        </DialogHeader>
                        <div className="mt-4 space-y-3">
                          <div>
                            <Input
                              placeholder="Category Name"
                              value={editingCategory?.name || ""}
                              onChange={(e) =>
                                editingCategory &&
                                setEditingCategory({ ...editingCategory, name: e.target.value })
                              }
                              className="w-full bg-neutral-800 border-neutral-700 text-neutral-100 placeholder-neutral-400"
                            />
                          </div>
                          <div className="flex justify-end gap-2 mt-4">
                            <Button
                              variant="outline"
                              size="sm"
                              className="border-neutral-700 hover:bg-neutral-700 text-white dark:border-neutral-700 dark:hover:bg-neutral-700 dark:text-white"
                              onClick={() => setEditingCategory(null)}
                            >
                              Cancel
                            </Button>
                            <Button
                              size="sm"
                              className="bg-indigo-600 hover:bg-indigo-700 text-white dark:bg-indigo-600 dark:hover:bg-indigo-700"
                              onClick={handleSaveEdit}
                            >
                              Save Changes
                            </Button>
                          </div>
                        </div>
                      </DialogContent>
                    </Dialog>

                    <Button
                      variant="ghost"
                      size="sm"
                      className="h-7 w-7 p-0 text-neutral-400 hover:text-white dark:text-neutral-400 dark:hover:text-white"
                      onClick={() => setIsAdding(true)}
                      aria-label="Add new category"
                    >
                      <Plus className="h-3.5 w-3.5" />
                    </Button>

                    <Button
                      variant="ghost"
                      size="sm"
                      className="h-7 w-7 p-0 text-neutral-400 hover:text-red-400 dark:text-neutral-400 dark:hover:text-red-400"
                      onClick={() => handleDeleteCategory(category.id)}
                      aria-label={`Delete ${category.name}`}
                    >
                      <Trash className="h-3.5 w-3.5" />
                    </Button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </>
  );
}

export default CategoryManagement;